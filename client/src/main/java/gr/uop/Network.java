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

        if(!receivedValid) {
            System.err.println("Received invlalid server message.");
            return;
        }

        System.out.println("===");
        var serverSaid = decoded.get("serverSaid").toString();
        System.out.println("Server Said: |" + serverSaid + "|");
        switch (serverSaid) {
            case "initialization":
                // TODO
                break;
            case "update":
                // TODO
                // different message contents depending on the subscription (secretary, manager, public-monitor)
                break;
            default:
                System.out.println("Server said: " + serverSaid);
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
