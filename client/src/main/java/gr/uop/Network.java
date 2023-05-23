package gr.uop;

import java.io.IOException;
import java.io.PrintWriter;
import java.net.Socket;
import java.net.UnknownHostException;
import java.nio.charset.Charset;
import java.nio.charset.StandardCharsets;
import java.util.Scanner;

public class Network {

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
        System.out.println("Proccessing: |" + message + "|");
        // TODO process message
    }

    /**
     * Calls {@link #received(String)} for each message that arrives.
     */
    private void startListeningForMessages() {
        new Thread(() -> {
            
            while( this.fromServer.hasNext() )
                this.received(fromServer.nextLine());
            // socket closed, either by client or server, should be client side 99% of the times
            // TODO notify the app that this client has been disconnected | maybe? call received("disconnected") and handle it there?

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
