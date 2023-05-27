package gr.uop.network;

import java.io.IOException;
import java.io.PrintWriter;
import java.net.Socket;
import java.nio.charset.Charset;
import java.nio.charset.StandardCharsets;
import java.util.List;
import java.util.Scanner;

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
    
            var decoded = Packet.decode(message); System.out.println(decoded);
            boolean receivedValid = decoded != null && decoded.get("request") != null;
    
            if(!receivedValid) {
                System.err.println("Received invlalid client message.");
                return;
            }

            switch(decoded.get("request").toString()) {
                case "subscribe": task = () -> {
                    String role = decoded.get("role").toString();
                    Subscription subscription = null;

                    if( role.equals("secretary") )
                        subscription = Subscription.SECRETARY;
                    else if( role.equals("manager") )
                        subscription = Subscription.MANAGER;
                    else if( role.equals("public-monitor") )
                        subscription = Subscription.PUBLIC_MONITOR;

                    if( subscription == null )
                        System.out.println("Invalid subscription attempt (" + role + ").");

                    subscribers.add(subscription, this);
                    this.send(model.toJSON(subscription).toJSONString());
                }; break;

                // case "": task = () -> {
                    // after model changes because of current task, call updateSubscribers(relevant subscriptions)
                // }; break;
    
                default: task = () -> {
                    System.out.println("Received: " + message);
                };
            }

            taskProcessor.process(task);
        }

        private void updateSubscribers(Subscription... ofSubscription) {
            for (Subscription subscription : ofSubscription) {
                var update = model.toJSON(subscription).toJSONString();
                subscribers.getAll(subscription).forEach(subscriber -> {
                    subscriber.send(update);
                });
            }
        }

    }

}
