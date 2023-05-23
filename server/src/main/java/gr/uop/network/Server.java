package gr.uop.network;

import java.io.IOException;
import java.net.ServerSocket;
import java.net.Socket;

import gr.uop.App;
import javafx.application.Platform;
import javafx.stage.WindowEvent;

public class Server extends Thread {

    private static int PORT = 23566;

    private ServerSocket LISTENING_SOCKET = null;

    // TODO class for the subsricers add remove and stuff

    @Override
    public void run() {
        try (ServerSocket LS = new ServerSocket(PORT)) {
            LISTENING_SOCKET = LS;
            
            // startServicingQueue();
                
            App.LOGGER.updateLog("Server started at port " + LS.getLocalPort() + ".");

            while (true) {
                Socket newConnectionSocket = LISTENING_SOCKET.accept();
                App.LOGGER.updateLog("New client connection.");
                
                var c = new Client(newConnectionSocket);
            }
        }
        catch (IOException e) { 
            if( LISTENING_SOCKET == null ) { // failed to initiate server listening socket
                this.shutdown();
                Platform.runLater(() -> {
                    App.stage.setOnCloseRequest(null);
                    App.stage.fireEvent(new WindowEvent(App.stage, WindowEvent.WINDOW_CLOSE_REQUEST));
                });
                System.err.println("Server already running on port " + PORT + ".");
            }
        }
    }

    public void shutdown() {
        try {
            if( this.LISTENING_SOCKET != null )
                this.LISTENING_SOCKET.close();
        }
        catch (IOException e) {

        }
    }

}

/*
subscribe: {
    secretary,
    managerlib,
    managerout,
    publicmonitor
}

disconnect
*/