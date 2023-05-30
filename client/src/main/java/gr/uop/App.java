package gr.uop;

import javafx.application.Application;
import javafx.application.Platform;
import javafx.fxml.FXMLLoader;
import javafx.geometry.Insets;
import javafx.geometry.Orientation;
import javafx.geometry.Pos;
import javafx.scene.Parent;
import javafx.scene.Scene;
import javafx.scene.control.Alert;
import javafx.scene.control.Button;
import javafx.scene.control.ButtonType;
import javafx.scene.control.Dialog;
import javafx.scene.control.Label;
import javafx.scene.control.Alert.AlertType;
import javafx.scene.image.Image;
import javafx.scene.image.ImageView;
import javafx.scene.input.KeyCode;
import javafx.scene.layout.HBox;
import javafx.scene.layout.Pane;
import javafx.scene.layout.Priority;
import javafx.scene.layout.StackPane;
import javafx.scene.layout.TilePane;
import javafx.scene.layout.VBox;
import javafx.stage.Modality;
import javafx.stage.Stage;
import java.io.IOException;
import java.util.LinkedList;
import java.util.Optional;
import java.util.concurrent.Semaphore;

import org.json.simple.JSONArray;

import gr.uop.ControllerSecretary.Media;

public class App extends Application {

    public static Network NETWORK;

    public static BaseController CONTROLLER;
      
    public static String lastIPPortGiven = "";

    public static Semaphore smp = new Semaphore(0);

    public static Scene scene;
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
            e.printStackTrace();
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

        public static String managerCallingState(String userid){
            Dialog<String> dialog = new Dialog<>();
            dialog.initOwner(stage);
            dialog.setTitle("Manager Action");

            VBox choiceContainer = new VBox();

            Label mainText = new Label("Calling "+userid);

            ButtonType arrived = new ButtonType("Arrived");
            ButtonType pause = new ButtonType("Pause");
            ButtonType discard = new ButtonType("Discard");
            
            ButtonType close = ButtonType.CLOSE;

            choiceContainer.getChildren().addAll(mainText);
            choiceContainer.setAlignment(Pos.CENTER);

            dialog.getDialogPane().setContent(choiceContainer);

            dialog.getDialogPane().getButtonTypes().addAll(arrived,pause,discard,close);

            dialog.setResultConverter((button) -> {
                System.out.println(button);
                if (button == arrived) {
                   return "arrived";
                }
                else if(button == pause){
                    return "pause";
                }
                else if(button == discard){
                    return "discard";
                }
                else {
                    return null;
                }
            });


            Optional<String> result = dialog.showAndWait();
            if (result.isPresent()) {
                return result.get();
            }
            else {
                return null;
            }
        }

        public static String managerFrozenState(String userid){
            Dialog<String> dialog = new Dialog<>();
            dialog.initOwner(stage);
            dialog.setTitle("Manager Action");

            VBox choiceContainer = new VBox();

            Label mainText = new Label("Waiting Action for "+userid);

            ButtonType arrived = new ButtonType("Arrived");
            ButtonType discard = new ButtonType("Discard");
            
            ButtonType close = ButtonType.CLOSE;

            choiceContainer.getChildren().addAll(mainText);
            choiceContainer.setAlignment(Pos.CENTER);

            dialog.getDialogPane().setContent(choiceContainer);

            dialog.getDialogPane().getButtonTypes().addAll(arrived,discard,close);

            dialog.setResultConverter((button) -> {
                System.out.println(button);
                if (button == arrived) {
                   return "arrived";
                }
                else if(button == discard){
                    return "discard";
                }
                else {
                    return null;
                }
            });


            Optional<String> result = dialog.showAndWait();
            if (result.isPresent()) {
                return result.get();
            }
            else {
                return null;
            }
            
        }

        public static String managerOcuppiedState(String userid){
            Dialog<String> dialog = new Dialog<>();
            dialog.initOwner(stage);
            dialog.setTitle("Manager Action");

            VBox choiceContainer = new VBox();

            Label mainText = new Label("Waiting Action for "+userid);

            ButtonType completed = new ButtonType("Completed");
            ButtonType completedPause = new ButtonType("Completed & Pause");
            
            ButtonType close = ButtonType.CLOSE;

            choiceContainer.getChildren().addAll(mainText);
            choiceContainer.setAlignment(Pos.CENTER);

            dialog.getDialogPane().setContent(choiceContainer);

            dialog.getDialogPane().getButtonTypes().addAll(completed,completedPause,close);

            dialog.setResultConverter((button) -> {
                System.out.println(button);
                if (button == completed) {
                   return "completed";
                }
                else if(button == completedPause){
                    return "completed-pause";
                }
                else {
                    return null;
                }
            });


            Optional<String> result = dialog.showAndWait();
            if (result.isPresent()) {
                return result.get();
            }
            else {
                return null;
            }
            
        }

        public static String managerPauseState(){
            Dialog<String> dialog = new Dialog<>();
            dialog.initOwner(stage);
            dialog.setTitle("Manager Action");

            VBox choiceContainer = new VBox();

            Label mainText = new Label("Waiting for action");

            ButtonType resume = new ButtonType("Resume");
            
            ButtonType close = ButtonType.CLOSE;

            choiceContainer.getChildren().addAll(mainText);
            choiceContainer.setAlignment(Pos.CENTER);

            dialog.getDialogPane().setContent(choiceContainer);

            dialog.getDialogPane().getButtonTypes().addAll(resume,close);

            dialog.setResultConverter((button) -> {
                if (button == resume) {
                   return "resume";
                }
                else {
                    return null;
                }
            });


            Optional<String> result = dialog.showAndWait();
            if (result.isPresent()) {
                return result.get();
            }
            else {
                return null;
            }
            
        }

        public static String managerAvailiableState(){
            Dialog<String> dialog = new Dialog<>();
            dialog.initOwner(stage);
            dialog.setTitle("Manager Action");

            VBox choiceContainer = new VBox();

            Label mainText = new Label("Availiable");

            ButtonType pause = new ButtonType("Pause");
            
            ButtonType close = ButtonType.CLOSE;

            choiceContainer.getChildren().addAll(mainText);
            choiceContainer.setAlignment(Pos.CENTER);

            dialog.getDialogPane().setContent(choiceContainer);

            dialog.getDialogPane().getButtonTypes().addAll(pause,close);

            dialog.setResultConverter((button) -> {
                System.out.println(button);
                if (button == pause) {
                   return "pause";
                }
                else {
                    return null;
                }
            });


            Optional<String> result = dialog.showAndWait();
            if (result.isPresent()) {
                return result.get();
            }
            else {
                return null;
            }
            
        }

        public static void managerViewQueue(String comapnyName,JSONArray waitingList,JSONArray unavailiableList){
            Dialog<Integer> dialog = new Dialog<>();
            dialog.initOwner(stage);
            dialog.setTitle(comapnyName);

            HBox mainContainer = new HBox();
            VBox waitingContainer = new VBox();
            VBox unavailiableContainer = new VBox();

            Label waitingLabel = new Label("Waiting");
            VBox waitingInterviwers = new VBox();
            for (int i=0;i<waitingList.size();i++) {
                waitingInterviwers.getChildren().add(new Label(waitingList.get(i)+""));
            }
            waitingInterviwers.setAlignment(Pos.CENTER);
            waitingContainer.getChildren().addAll(waitingLabel,waitingInterviwers);

            Label unavailiableLabel = new Label("Unavailiable");
            VBox unavailiableInterviwers = new VBox();
            for (int i=0;i<unavailiableList.size();i++) {
                unavailiableInterviwers.getChildren().add(new Label(unavailiableList.get(i)+""));
            }
            unavailiableInterviwers.setAlignment(Pos.CENTER);
            unavailiableContainer.getChildren().addAll(unavailiableLabel,unavailiableInterviwers);

            mainContainer.getChildren().addAll(waitingContainer,unavailiableContainer);
            mainContainer.setSpacing(10);

            dialog.getDialogPane().setContent(mainContainer);

            dialog.getDialogPane().getButtonTypes().addAll(ButtonType.OK);

            dialog.show();
            
        }

        public static void InformUser(String mainText, String headerText){
            var alert = new Alert(AlertType.INFORMATION);
            alert.initModality(Modality.APPLICATION_MODAL);
            alert.initOwner(stage);
            alert.setTitle("");
            alert.setHeaderText(headerText);
            alert.setContentText(mainText);
            alert.show();
        }

        public static String secretaryConfirmInsert(Media list){
            Alert alert = new Alert(AlertType.CONFIRMATION);
            alert.setTitle("Confirmation");
            alert.setContentText("Do you want to proceed inserting a user into companies "+list);
            alert.setHeaderText(null);
            alert.initModality(Modality.WINDOW_MODAL);
            alert.initOwner(stage);
          
            Optional<ButtonType> result = alert.showAndWait();
            if (result.get() == ButtonType.OK) {
                return "OK";
            }
            else if (result.get() == ButtonType.CANCEL) {
                return "Cancel";
            }
            return "Cancel";
        }

        public static void secretaryIllegalSearch(String msg){
            var alert = new Alert(AlertType.ERROR);
            alert.initModality(Modality.APPLICATION_MODAL);
            alert.initOwner(stage);
            alert.setTitle("");
            alert.setHeaderText("Field Empty");
            alert.setContentText(msg);
            alert.show(); 
        }

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
