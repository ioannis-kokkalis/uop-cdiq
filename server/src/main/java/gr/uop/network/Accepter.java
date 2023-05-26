package gr.uop.network;

import java.io.IOException;
import java.net.ServerSocket;
import java.net.Socket;
import java.net.SocketException;

public class Accepter {
    
    private final TaskProcessor taskProcessor;
    private final Subscribers subscribers;

    private final ServerSocket listener;

    public Accepter(Network network, int portToListen, TaskProcessor taskProcessor, Subscribers subscribers) throws IOException {
        this.taskProcessor = taskProcessor;
        this.subscribers = subscribers;
        this.listener = new ServerSocket(portToListen);

        new Thread(() -> {
            try {
                while (true) {
                    Socket accepted = this.listener.accept();

                    System.out.println("Connection accepted.");

                    Task establishAcceptedConnection = () -> {
                        try {
                            var client = new Client(accepted, this.taskProcessor, this.subscribers);
                            // TODO keep client in a different list than subscribers to disconnect him in any case even if he does not subscribe after connecitng
                        } catch (IOException e) {
                            System.out.println("Failed to establish client communication.");
                            return;
                        }
                        System.out.println("Client communication established.");                       
                    };

                    this.taskProcessor.process(establishAcceptedConnection);
                }

            } catch (SocketException e) {
                System.out.println("Network accepter shutdown.");
            } catch (IOException e) {
                System.out.println("Exception while listening to accept connections.");
            }
        }).start();
    }

    public void shutdown() {
        try {
            listener.close();
        } catch (IOException e) {
            System.out.println("Exception while shuting down accepters listener.");
        }
    }

}
