package gr.uop;

import javafx.application.Application;
import javafx.fxml.FXMLLoader;
import javafx.geometry.Insets;
import javafx.scene.Parent;
import javafx.scene.Scene;
import javafx.scene.control.Label;
import javafx.scene.image.Image;
import javafx.scene.input.KeyCode;
import javafx.scene.layout.Pane;
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
        }
        catch (Exception e) {
            // e.printStackTrace();
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
            if( event.getCode() == KeyCode.F11)
                App.stage.setFullScreen(!App.stage.isFullScreen());
        });
        App.stage.setFullScreenExitHint("");
    }

}
