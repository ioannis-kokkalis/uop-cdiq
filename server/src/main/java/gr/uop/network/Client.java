package gr.uop.network;

import java.io.IOException;
import java.io.PrintWriter;
import java.net.Socket;
import java.nio.charset.Charset;
import java.nio.charset.StandardCharsets;
import java.util.Scanner;

public class Client {

    private final static Charset MESSAGES_ENCODING = StandardCharsets.UTF_16;

    private final Socket socket;
    private final PrintWriter toClient;
    private final Scanner fromClient;

    public Client(Socket socket) throws IOException {
        this.socket = socket;
        this.toClient = new PrintWriter(this.socket.getOutputStream(), true, MESSAGES_ENCODING);
        this.fromClient = new Scanner(this.socket.getInputStream(), MESSAGES_ENCODING);

        this.startListeningForMessages();
    }

    /**
     * *In case client is disconnected, does nothing.
     * @param message to send
     */
    public void send(String message) {
        toClient.println(message);
    }

    /**
     * @param message that arrived from the client
     */
    private void received(String message) {
        System.out.println("Proccessing: |" + message + "|");
        // TODO process message
    }

    /**
     * Calls {@link #received(String)} for each message that arrives.
     */
    private void startListeningForMessages() {
        new Thread(() -> {
            
            while( this.fromClient.hasNext() )
                this.received(fromClient.nextLine());
            // socket closed, either by client or server, should be client side 99% of the times
            // TODO notify the server that this client has been disconnected | maybe? call received("disconnected") and handle it there?

        }).start();
    }

}
