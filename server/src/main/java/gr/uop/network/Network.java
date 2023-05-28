package gr.uop.network;

import java.io.IOException;
import java.io.PrintWriter;
import java.net.Socket;
import java.nio.charset.Charset;
import java.nio.charset.StandardCharsets;
import java.util.HashMap;
import java.util.List;
import java.util.Scanner;

import org.json.simple.JSONArray;
import org.json.simple.JSONObject;

import gr.uop.model.Model;
import gr.uop.network.Subscribers.Subscription;

public class Network {

    private final TaskProcessor taskProcessor;
    private final Subscribers subscribers;
    private final Accepter accepter;

    private final Model model;

    public Network(int port, Model model) throws NetworkException {
        try {
            this.model = model;
            this.taskProcessor = new TaskProcessor();
            this.subscribers = new Subscribers();
            this.accepter = new Accepter(this, port, taskProcessor);
        } catch (IOException e) {
            shutdown();
            throw new NetworkException(e.getMessage());
        }
    }

    public void shutdown() {
        accepter.shutdown();
        taskProcessor.shutdown();
    }

    public class Client {

        private final static Charset ENCODING = StandardCharsets.UTF_16;
    
        private final Socket socket;
        private final PrintWriter toClient;
        private final Scanner fromClient;

        public Client(Socket socket, List<Client> clients) throws IOException {
            this.socket = socket;
            this.toClient = new PrintWriter(this.socket.getOutputStream(), true, ENCODING);
            this.fromClient = new Scanner(this.socket.getInputStream(), ENCODING);
    
            new Thread(() -> {
                while (this.fromClient.hasNextLine()) {
                    received(this.fromClient.nextLine());
                }
                subscribers.remove(this);
                clients.remove(this);
                System.out.println("Client disconnected.");
            }).start();
        }
    
        public void disconnect() {
            try {
                this.socket.close();
            } catch (IOException e) {
                System.err.println("Unable to disconnect client.");
            }
        }
    
        public void send(String message) {
            this.toClient.println(message);
        }
    
        private void received(String message) {
            Task task = null;
    
            var decoded = Packet.decode(message);
            boolean receivedValid = decoded != null && decoded.get("request") != null;
    
            if(!receivedValid) {
                System.err.println("Received invlalid client message.");
                return;
            }

            var request = decoded.get("request").toString();
            System.out.println("Received: |" + request + "|");
            switch (request) {
                case "subscribe":
                    task = () -> {
                        String role = decoded.get("role").toString();
                        Subscription subscription = null;

                        if (role.equals("secretary"))
                            subscription = Subscription.SECRETARY;
                        else if (role.equals("manager"))
                            subscription = Subscription.MANAGER;
                        else if (role.equals("public-monitor"))
                            subscription = Subscription.PUBLIC_MONITOR;

                        if (subscription == null)
                            System.out.println("Invalid subscription attempt (" + role + ").");

                        subscribers.add(subscription, this);

                        var info = model.infoJSON();
                        info.put("serverSaid", "initialization");
                        this.send(Packet.encode(info));

                        var data = model.toJSONforSubscribersOf(subscription, false);
                        data.put("serverSaid", "update");
                        this.send(Packet.encode(data));
                    };
                    break;

                case "user-info":
                    task = () -> {
                        // Expecting:
                        // { 
                        //     "request": "user-info",
                        //     "id": "2", // may be ""
                        //     "name": "username", // may be ""
                        //     "secret": "usersecret" // may be ""
                        // }

                        int id = -1;
                        try {
                            id = Integer.parseInt(decoded.get("id").toString());
                        } catch (NumberFormatException e) {}
                        var name = decoded.get("name").toString();
                        var secret = decoded.get("secret").toString();

                        var user = model.getUser(id, name, secret);
                        
                        var map = new HashMap<String, Object>();
                        map.put("serverSaid", "user-info");

                        if( user == null ) {
                            map.put("result", "not-found");
                            map.put("id", "" + id);
                            map.put("name", name);
                            map.put("secret", secret);
                        }
                        else /* found */ {
                            map.put("result", "found");
                            map.put("id", "" + user.getID());
                            map.put("name", user.getName());
                            map.put("secret", user.getSecret());
                            var arr = new JSONArray();
                            user.getCompaniesRegisteredAt().forEach(company -> {
                                arr.add("" + company.getID());
                            });
                            map.put("companies-registered", arr);
                        }

                        var response = new JSONObject(map);
                        this.send(Packet.encode(response));
                    };
                    break;

                case "user-register":
                    task = () -> {
                        /* Expecting
                        {
                            "request": "user-register",
                            "name": "username και έλλ",
                            "secret": "usersecret",
                            "companies-to-register": ["2","7","16"]
                        }
                         */

                        // after model changes because of current task, call updateSubscribers(relevant subscriptions)
                        // TODO respond (check client side expectations for the request)
                    };
                    break;
                case "user-insert":
                    task = () -> {
                        // Expecting
                        // {
                        //     "request": "user-insert",
                        //     "id": "2",
                        //     "companies-to-register": ["2","7","16"]
                        // }
                        // after model changes because of current task, call updateSubscribers(relevant subscriptions)
                        // TODO respond (check client side expectations for the request)
                    };
                    break;

                // case "":
                //     task = () -> {
                //         // after model changes because of current task, call updateSubscribers(relevant subscriptions)
                //     };
                //     break;

                default:
                    task = () -> {
                        System.out.println("Unknown request (" + request + ").");
                    };
            }

            taskProcessor.process(task);
        }

        private void updateSubscribers(Subscription... ofSubscription) {
            for (Subscription subscription : ofSubscription) {

                var update = model.toJSONforSubscribersOf(subscription, true);
                update.put("serverSaid", "update");
                var updatePacket = Packet.encode(update);

                subscribers.getAll(subscription).forEach(subscriber -> {
                    subscriber.send(updatePacket);
                });
            }
        }

    }

}
