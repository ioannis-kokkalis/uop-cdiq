package gr.uop;

import javafx.application.Application;
import javafx.application.Platform;
import javafx.fxml.FXMLLoader;
import javafx.geometry.Insets;
import javafx.scene.Parent;
import javafx.scene.Scene;
import javafx.scene.control.Alert;
import javafx.scene.control.Label;
import javafx.scene.control.Alert.AlertType;
import javafx.scene.image.Image;
import javafx.scene.input.KeyCode;
import javafx.scene.layout.Pane;
import javafx.stage.Modality;
import javafx.stage.Stage;
import java.io.IOException;

public class App extends Application {

    public static Network NETWORK;
    
    public static String lastIPPortGiven = "";

    private static Scene scene;
    private static Stage stage;

    @Override
    public void start(Stage stage) throws IOException {
        App.stage = stage;
        App.scene = new Scene(new Pane()); // init scene with dummy root
        stage.setScene(scene);
        
        prepareEyeCandy();
        prepareFullscreenToggle();
        
        App.setRoot("launcher");
        
        stage.show();
        
        scene.getRoot().requestFocus(); // focus nothing at launch
    }

    public static void main(String[] args) {
        launch();
    }

    public static void setRoot(String component) {
        Parent root = null;

        try {
            root = new FXMLLoader(App.class.getResource("FXMLcomponent/" + component + ".fxml")).load();
        } catch (Exception e) {
            Label oops = new Label("FXML component \"" + component + ".fxml\" could not load.");
            oops.setPadding(new Insets(64, 128, 64, 128));
            root = oops;
        }

        App.scene.setRoot(root);
    }

    public static void replaceCloseWithOneTimeBackToLauncher() {
        App.stage.setOnCloseRequest(event -> {
            event.consume();

            App.NETWORK.disconnect();
            App.NETWORK = null;

            App.setRoot("launcher");

            App.stage.setOnCloseRequest(null);
        });
    }

    private static void prepareEyeCandy() {
        stage.setTitle("Career Day");
        stage.getIcons().add(new Image(App.class.getResourceAsStream("media/logo.png")));
    }

    private static void prepareFullscreenToggle() {
        App.scene.setOnKeyPressed(event -> {
            if(event.getCode() == KeyCode.F11)
                App.stage.setFullScreen(!App.stage.isFullScreen());
        });
        App.stage.setFullScreenExitHint("");
    }

    public static void consoleLog(String main, String... extra) {
        var sb = new StringBuilder();

        sb.append("\n===========\n").append(main);

        for (String e : extra)
            sb.append("\n-----------\n").append(e);

        sb.append("\n===========\n");

        System.out.println(sb.toString());
    }

    public static void consoleLogError(String main, String... extra) {
        var sb = new StringBuilder();

        sb.append("\u001B[31m\n===========\n").append(main);

        for (String e : extra)
            sb.append("\n-----------\n").append(e);

        sb.append("\n===========\n\u001B[0m");

        System.err.println(sb.toString());
    }

    public static class Alerts {

        public static void serverConnectionTerminated() {
            Platform.runLater(() -> {
                var alert = new Alert(AlertType.INFORMATION);
                alert.initModality(Modality.APPLICATION_MODAL);
                alert.initOwner(stage);
                alert.setTitle("");
                alert.setHeaderText("Server connection dropped!");
                alert.setContentText("You will no longer receive updates from server until you attempt to initiate a new connection.");
                alert.show();
                if( App.stage.getOnCloseRequest() != null ) // not in launcher
                    scene.getRoot().setDisable(true);
            });
        }

        public static void serverConnectionFailed() {
            Platform.runLater(() -> {
                var alert = new Alert(AlertType.INFORMATION);
                alert.initModality(Modality.APPLICATION_MODAL);
                alert.initOwner(stage);
                alert.setTitle("");
                alert.setHeaderText("Failed to initiate server connection!");
                alert.setContentText("Ensure that host and port values are valid and point to an active server.");
                alert.show();
            });
        }

    }

}
