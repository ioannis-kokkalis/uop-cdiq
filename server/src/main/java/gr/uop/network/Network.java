package gr.uop.network;

import java.io.IOException;

public class Network {

    private final TaskProcessor taskProcessor;
    private final Subscribers subscribers;
    private final Accepter accepter;

    public Network(int port) throws NetworkException {
        try {
            this.taskProcessor = new TaskProcessor();
            this.subscribers = new Subscribers();
            this.accepter = new Accepter(this, port, taskProcessor, subscribers);
        } catch (IOException e) {
            shutdown();
            throw new NetworkException(e.getMessage());
        }
    }

    public void shutdown() {
        accepter.shutdown();
        taskProcessor.shutdown();
    }

}
