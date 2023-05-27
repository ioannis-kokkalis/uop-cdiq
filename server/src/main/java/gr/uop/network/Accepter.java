package gr.uop.network;

import java.io.IOException;
import java.net.ServerSocket;
import java.net.Socket;
import java.net.SocketException;
import java.util.LinkedList;

import gr.uop.network.Network.Client;

public class Accepter {
    
    private final TaskProcessor taskProcessor;

    private final ServerSocket listener;
    private final LinkedList<Client> clients;

    public Accepter(Network network, int portToListen, TaskProcessor taskProcessor) throws IOException {
        this.taskProcessor = taskProcessor;
        this.listener = new ServerSocket(portToListen);
        this.clients = new LinkedList<>();

        new Thread(() -> {
            try {
                while (true) {
                    Socket accepted = this.listener.accept();

                    System.out.println("Connection accepted.");

                    Task establishAcceptedConnection = () -> {
                        Client client;
                        try {
                            client = network.new Client(accepted, clients);
                        } catch (IOException e) {
                            System.out.println("Failed to establish client communication.");
                            return;
                        }
                        System.out.println("Client communication established.");
                        clients.add(client);
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
            clients.forEach(c -> c.disconnect());
        } catch (IOException e) {
            System.out.println("Exception while shuting down accepters listener.");
        }
    }

}