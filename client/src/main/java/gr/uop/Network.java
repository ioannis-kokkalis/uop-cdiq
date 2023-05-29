package gr.uop;

import java.io.IOException;
import java.io.PrintWriter;
import java.net.Socket;
import java.nio.charset.Charset;
import java.nio.charset.StandardCharsets;
import java.util.Scanner;

import org.json.simple.JSONObject;
import org.json.simple.parser.JSONParser;
import org.json.simple.parser.ParseException;

public class Network {

    public class Packet {

        public static String encode(JSONObject message) {
            return message.toJSONString();
        }

        public static JSONObject decode(String message) {
            JSONObject decoded = null;

            try {
                decoded = (JSONObject) new JSONParser().parse(message);
            } catch (ParseException e) {
                App.consoleLogError("Failed to decode client message.", message);
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
     * @param message to send
     */
    public void send(String message) {
        toServer.println(message);
    }

    /**
     * @param message that arrived from the server
     */
    private void received(String message) {
        if( message == null ) {
            App.Alerts.serverConnectionTerminated();
            return;
        }

        var decoded = Packet.decode(message);
        boolean receivedValid = decoded != null && decoded.get("serverSaid") != null;

        if (!receivedValid) {
            App.consoleLogError("Received invlalid server message.", message);
            return;
        }

        var serverSaid = decoded.get("serverSaid").toString();
        App.consoleLog("Received Packet", decoded.toString());
        switch (serverSaid) {
            case "initialization":
                // TODO
                break;
            case "update":
                // TODO
                // different message contents depending on the subscription (secretary, manager, public-monitor)
                break;
            case "user-info":
                /* TODO Handle Response: User Found
                {
                    "result": "found",
                    "id": "2", // you sent from ui
                    "name": "somename",
                    "secret": "somesecret",
                    "companies-registered": ["1","14","5"]
                }
                 */
                /* TODO Handle Response: User Not Found
                {
                    "result": "not-found",
                    "id": "2", // you sent from ui
                    "name": "somename", // you sent from ui
                    "secret": "somesecret" // you sent from ui
                }
                 */
                break;
            case "user-register":
                /* TODO Handle Response: Always
                {
                    "result": "ok",
                    "id": "5", // generated from the system
                    "name": "somename", // you sent from ui
                    "secret": "somesecret" // you sent from ui
                }
                 */
                break;
            case "user-insert":
                /* TODO Handle Response: ID -did- match User
                {
                    "result": "ok",
                    "id": "5", // you sent from ui
                    "name": "somename",
                    "secret": "somename"
                }
                */
                /* TODO Handle Response: ID -did not- match User
                {
                    "result": "not-ok",
                    "id": "5" // you sent from ui
                }
                 */
                break;
            default:
                App.consoleLogError("Unknown Server Said", serverSaid);
        }
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
