package gr.uop.network;

import java.io.IOException;
import java.io.PrintWriter;
import java.net.Socket;
import java.nio.charset.Charset;
import java.nio.charset.StandardCharsets;
import java.util.Scanner;

public class Client {

    private final static Charset ENCODING = StandardCharsets.UTF_16;

    private final Socket socket;
    private final PrintWriter toClient;
    private final Scanner fromClient;

    private final TaskProcessor taskProcessor;
    private final Subscribers subscribers;

    public Client(Socket socket, TaskProcessor taskProcessor, Subscribers subscribers) throws IOException {
        this.socket = socket;
        this.toClient = new PrintWriter(this.socket.getOutputStream(), true, ENCODING);
        this.fromClient = new Scanner(this.socket.getInputStream(), ENCODING);

        this.taskProcessor = taskProcessor;
        this.subscribers = subscribers;

        new Thread(() -> {
            while (this.fromClient.hasNextLine()) {
                received(this.fromClient.nextLine());
            }
            System.out.println("Client disconnected.");
        }).start();
    }

    public void disconnect() {
        try {
            this.socket.close();
        } catch (IOException e) {

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
                System.out.println("Subscribe: " + decoded.toString());
            }; break;

            default: task = () -> {
                System.out.println("Received: " + message);
            };
        }

        this.taskProcessor.process(task);
    }

}
