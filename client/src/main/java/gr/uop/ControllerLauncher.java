package gr.uop;

import java.io.IOException;
import java.net.URL;
import java.util.LinkedList;
import java.util.ResourceBundle;
import java.util.regex.Matcher;
import java.util.regex.Pattern;

import javafx.fxml.FXML;
import javafx.fxml.Initializable;
import javafx.scene.control.Alert;
import javafx.scene.control.Button;
import javafx.scene.control.TextField;
import javafx.scene.control.Alert.AlertType;
import javafx.stage.Modality;

public class ControllerLauncher implements Initializable {
    
    @FXML private Button buttonS;
    @FXML private Button buttonML;
    @FXML private Button buttonMOA;
    @FXML private Button buttonPM;
    private LinkedList<Button> clientOptionButtons = new LinkedList<>();

    @FXML private TextField inputIP;

    private final static Pattern HOSTPORTPATTERN = Pattern.compile("^(?:\\d{1,3}\\.\\d{1,3}\\.\\d{1,3}\\.\\d{1,3}|localhost):[1-9]\\d*$");

    @Override
    public void initialize(URL location, ResourceBundle resources) {
        clientOptionButtons.add(buttonS);
        clientOptionButtons.add(buttonML);
        clientOptionButtons.add(buttonMOA);
        clientOptionButtons.add(buttonPM);
        clientOptionButtons.forEach(b -> b.setDisable(true));

        inputIP.textProperty().addListener((obs, o, n) -> {
            clientOptionButtons.forEach(b -> b.setDisable(!HOSTPORTPATTERN.matcher(n).find()));
        });

        inputIP.setText(App.lastIPPortGiven);
    }
    
    public void openSecretary() {
        connect("secretary", () -> {
            App.setRoot("secretary");
        });
    }
    
    public void openManagerOpenArea() {
        connect("managerout", () -> {
            App.setRoot("manager");
        });
    }
    
    public void openManagerLibrary() {
        connect("managerlib", () -> {
            App.setRoot("manager");
        });
    }
    
    public void openPublicMonitor() {
        connect("publicmonitor", () -> {
            App.setRoot("public-monitor");
        });
    }

    public void connect(String asRole, Runnable onSuccessRun) {
        inputIP.setDisable(true);
        App.lastIPPortGiven = inputIP.getText();
        
        var temp = App.lastIPPortGiven.split(":"); // input listener confirmed
        var host = temp[0];
        var port = Integer.parseInt(temp[1]);
        
        inputIP.setText("Trying to connect...");

        try {
            App.NETWORK = new Network(host, port);
        } 
        catch (IOException e) {
            App.NETWORK = null;
        }

        if( App.NETWORK != null ) {
            inputIP.setText("Connected");
            inputIP.setDisable(true);
            clientOptionButtons.forEach(b -> b.setDisable(true));

            App.replaceCloseWithOneTimeBackToLauncher();
            App.NETWORK.send("subscribe:" + asRole);
            onSuccessRun.run();
        }
        else {
            var alert = new Alert(AlertType.INFORMATION);
            alert.initModality(Modality.APPLICATION_MODAL);
            alert.setHeaderText("Failed to connect!");
            alert.show();

            inputIP.setText(App.lastIPPortGiven);
            inputIP.setDisable(false);
            inputIP.requestFocus();
        }
    }

}
