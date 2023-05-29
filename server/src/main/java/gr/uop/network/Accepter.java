package gr.uop.network;

import java.io.IOException;
import java.net.ServerSocket;
import java.net.Socket;
import java.net.SocketException;
import java.util.LinkedList;

import gr.uop.App;
import gr.uop.Task;
import gr.uop.network.Network.Client;

public class Accepter {
    
    private final ServerSocket listener;
    private final LinkedList<Client> clients;

    public Accepter(Network network, int portToListen) throws IOException {
        this.listener = new ServerSocket(portToListen);
        this.clients = new LinkedList<>();

        new Thread(() -> {
            try {
                while (true) {
                    Socket accepted = this.listener.accept();

                    App.consoleLog("Connection accepted.");

                    Task establishAcceptedConnection = () -> {
                        Client client;
                        try {
                            client = network.new Client(accepted, clients);
                        } catch (IOException e) {
                            App.consoleLogError("Failed to establish client communication.");
                            return;
                        }
                        App.consoleLog("Client communication established.");
                        clients.add(client);
                    };

                    App.TASK_PROCESSOR.process(establishAcceptedConnection);
                }

            } catch (SocketException e) {
                App.consoleLog("Network accepter shutdown.");
            } catch (IOException e) {
                App.consoleLogError("Exception while listening to accept connections.");
            }
        }).start();
    }

    public void shutdown() {
        try {
            listener.close();
            clients.forEach(c -> c.disconnect());
        } catch (IOException e) {
            App.consoleLogError("Exception while shuting down accepters listener.");
        }
    }

}
