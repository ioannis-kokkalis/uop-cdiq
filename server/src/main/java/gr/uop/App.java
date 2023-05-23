package gr.uop;

import java.io.IOException;
import java.util.Optional;

import gr.uop.network.Server;
import javafx.application.Application;
import javafx.fxml.FXMLLoader;
import javafx.scene.Scene;
import javafx.scene.control.Alert;
import javafx.scene.control.Alert.AlertType;
import javafx.scene.control.ButtonType;
import javafx.stage.Modality;
import javafx.stage.Stage;

/**
 * JavaFX App
 */
public class App extends Application {

    public static Server SERVER;
    public static Controller LOGGER;
    public static Stage stage;

    @Override
    public void start(Stage stage) throws IOException {
        App.stage = stage;

        stage.setTitle("Career Day Server | Do NOT close this window until the end of the event!!!");

        try { 
            stage.setScene(new Scene(new FXMLLoader(App.class.getResource("log.fxml")).load()));
        }
        catch (Exception e) { }
    
        stage.setOnCloseRequest(event -> {
            var dialog = new Alert(AlertType.CONFIRMATION);
            dialog.initModality(Modality.APPLICATION_MODAL);
            dialog.initOwner(stage);
            dialog.setHeaderText("Server Termination Confirmation");
            dialog.setContentText("THIS ACTION WILL TERMINATE THE SERVER!!!");

            Optional<ButtonType> buttonPressed = dialog.showAndWait();

            if( buttonPressed.get().equals(ButtonType.OK) ) {
                App.SERVER.shutdown();
            }
            else {
                event.consume();
            }
        });

        stage.show();

        App.SERVER = new Server();
        App.SERVER.start();

        // TODO add backup system for model current state
    }

    public static void main(String[] args) {
        launch();
    }

}
