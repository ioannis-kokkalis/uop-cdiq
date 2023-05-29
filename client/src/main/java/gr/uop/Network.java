package gr.uop;

import java.io.IOException;
import java.io.PrintWriter;
import java.net.Socket;
import java.nio.charset.Charset;
import java.nio.charset.StandardCharsets;
import java.util.ArrayList;
import java.util.Scanner;
import org.json.simple.JSONArray;
import org.json.simple.JSONObject;
import org.json.simple.parser.JSONParser;
import org.json.simple.parser.ParseException;

public class Network {
    public static class Packet {

        public static String encode(JSONObject message) {
            return message.toJSONString();
        }

        public static JSONObject decode(String message) {
            JSONObject decoded = null;

            try {
                decoded = (JSONObject) new JSONParser().parse(message);
            } catch (ParseException e) {
                System.err.println("Failed to decode client message: " + message);
            }

            return decoded;
        }

    }

    private final static Charset MESSAGES_ENCODING = StandardCharsets.UTF_16;

    private final Socket socket;
    private final PrintWriter toServer;
    private final Scanner fromServer;

    public Network(String host, int port) throws IOException {
        this.socket = new Socket(host, port);
        this.toServer = new PrintWriter(this.socket.getOutputStream(), true, MESSAGES_ENCODING);
        this.fromServer = new Scanner(this.socket.getInputStream(), MESSAGES_ENCODING);

        this.startListeningForMessages();
    }

    /**
     * *In case server is disconnected, does nothing.
     * 
     * @param message to send
     */
    public void send(String message) {
        toServer.println(message);
    }

    /**
     * @param message that arrived from the server
     */
    private void received(String message) {
        ArrayList<Object>[] data = null;
        JSONArray array;
        JSONObject obj;

        if (message == null) {
            App.Alerts.serverConnectionTerminated();
            return;
        }

        var decoded = Packet.decode(message);
        boolean receivedValid = decoded != null && decoded.get("serverSaid") != null;

        if (!receivedValid) {
            System.err.println("Received invlalid server message.");
            return;
        }

        System.out.println("===");
        var serverSaid = decoded.get("serverSaid").toString();
        System.out.println("Server Said: |" + serverSaid + "|");
        switch (serverSaid) {
            case "initialization":
                try {
                    App.smp.acquire();
                } catch (InterruptedException e) {
                    e.printStackTrace();
                }
                array = (JSONArray) decoded.get("companies");

                data = new ArrayList[array.size()];
                for (int i = 0; i < data.length; i++) {
                    data[i] = new ArrayList<Object>(); // create a new ArrayList for each element
                }

                for (int i = 0; i < array.size(); i++) {
                    obj = (JSONObject) array.get(i);
                    data[i].add(obj.get("image-name"));
                    data[i].add(obj.get("name"));
                    data[i].add(obj.get("id"));
                    data[i].add(obj.get("table"));
                }

                if (App.CONTROLLER instanceof ControllerPublicMonitor)
                    ((ControllerPublicMonitor) App.CONTROLLER).setEnvironment(data);
                else if (App.CONTROLLER instanceof ControllerSecretary)
                    ((ControllerSecretary) App.CONTROLLER).setEnvironment(data);
                else if (App.CONTROLLER instanceof ControllerManager)
                    ((ControllerManager) App.CONTROLLER).setEnvironment(data);

                break;
            case "update":

                if (App.CONTROLLER instanceof ControllerPublicMonitor) {

                    array = (JSONArray) decoded.get("companies");

                    data = new ArrayList[array.size()];
                    for (int i = 0; i < data.length; i++) {
                        data[i] = new ArrayList<Object>(); // create a new ArrayList for each element
                    }

                    for (int i = 0; i < array.size(); i++) {
                        obj = (JSONObject) array.get(i);
                        data[i].add(obj.get("user-id"));
                        data[i].add(obj.get("id"));
                        data[i].add(obj.get("state"));

                    }
                    ((ControllerPublicMonitor) App.CONTROLLER).updateEnvironment(data);
                }
                else if(App.CONTROLLER instanceof ControllerManager){
                    array = (JSONArray) decoded.get("companies");

                    data = new ArrayList[array.size()];
                    for (int i = 0; i < data.length; i++) {
                        data[i] = new ArrayList<Object>(); // create a new ArrayList for each element
                    }

                    for (int i = 0; i < array.size(); i++) {
                        obj = (JSONObject) array.get(i);
                        data[i].add(obj.get("user-id"));
                        data[i].add(obj.get("id"));
                        data[i].add(obj.get("state"));
                        data[i].add(obj.get("user-id-queue-unavailable"));
                        data[i].add(obj.get("user-id-queue-waiting"));
                    }
                    ((ControllerManager) App.CONTROLLER).updateEnvironment(data);
                }
                
                break;
            case "user-info":

                data = new ArrayList[decoded.size()];
                for (int i = 0; i < data.length; i++) {
                    data[i] = new ArrayList<Object>(); // create a new ArrayList for each element
                }

                data[0].add(decoded.get("result"));
                data[1].add(decoded.get("name"));
                data[2].add(decoded.get("id"));
                data[3].add(decoded.get("secret"));

                JSONArray companies = (JSONArray) decoded.get("companies-registered");

                if(companies!=null){
                    for (int i = 0; i < companies.size(); i++) {
                        data[4].add(companies.get(i));
                    }
                }

                ((ControllerSecretary)App.CONTROLLER).informSearch(data);

                /*
                 * TODO Handle Response: User Found
                 * {
                 * "result": "found",
                 * "id": "2", // you sent from ui
                 * "name": "somename",
                 * "secret": "somesecret",
                 * "companies-registered": ["1","14","5"]
                 * }
                 */
                /*
                 * TODO Handle Response: User Not Found
                 * {
                 * "result": "not-found",
                 * "id": "2", // you sent from ui
                 * "name": "somename", // you sent from ui
                 * "secret": "somesecret" // you sent from ui
                 * }
                 */
                break;
            case "user-register":
                data = new ArrayList[decoded.size()];
                for (int i = 0; i < data.length; i++) {
                    data[i] = new ArrayList<Object>(); // create a new ArrayList for each element
                }

                data[0].add(decoded.get("result"));
                data[1].add(decoded.get("name"));
                data[2].add(decoded.get("id"));
                data[3].add(decoded.get("secret"));

                ((ControllerSecretary)App.CONTROLLER).informRegister(data);
                
                /*
                 * TODO Handle Response: Always
                 * {
                 * "result": "ok",
                 * "id": "5", // generated from the system
                 * "name": "somename", // you sent from ui
                 * "secret": "somesecret" // you sent from ui
                 * }
                 */
                break;
            case "user-insert":

                data = new ArrayList[decoded.size()];
                for (int i = 0; i < data.length; i++) {
                    data[i] = new ArrayList<Object>(); // create a new ArrayList for each element
                }

                data[0].add(decoded.get("result"));
                data[1].add(decoded.get("name"));

                if(decoded.get("id")!=null)
                    data[2].add(decoded.get("id"));

                if(decoded.get("secret")!=null) 
                    data[3].add(decoded.get("secret"));

                ((ControllerSecretary)App.CONTROLLER).informInsert(data);

                /*
                 * TODO Handle Response: ID -did- match User
                 * {
                 * "result": "ok",
                 * "id": "5", // you sent from ui
                 * "name": "somename",
                 * "secret": "somename"
                 * }
                 */
                /*
                 * TODO Handle Response: ID -did not- match User
                 * {
                 * "result": "not-ok",
                 * "id": "5" // you sent from ui
                 * }
                 */
                break;
            default:
                System.out.println("Server said unkown: " + serverSaid);
        }
        System.out.println("--- (Decoded) ---");
        System.out.println(decoded);
        System.out.println("===");
    }

    /**
     * Calls {@link #received(String)} for each message that arrives.
     */
    private void startListeningForMessages() {

        new Thread(() -> {

            while (this.fromServer.hasNext())
                this.received(fromServer.nextLine());
            this.received(null); // indicate connection termination

        }).start();
    }

    public void disconnect() {
        try {
            socket.close();
        } catch (IOException e) {
            e.printStackTrace();
        }
    }

}
