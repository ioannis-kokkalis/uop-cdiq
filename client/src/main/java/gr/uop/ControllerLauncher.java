package gr.uop;

import java.io.IOException;
import java.net.URL;
import java.util.HashMap;
import java.util.LinkedList;
import java.util.ResourceBundle;
import java.util.regex.Pattern;

import org.json.simple.JSONObject;

import gr.uop.Network.Packet;
import javafx.fxml.FXML;
import javafx.fxml.Initializable;
import javafx.scene.control.Button;
import javafx.scene.control.TextField;

public class ControllerLauncher implements Initializable {
    
    private enum Role {
        SECRETARY("secretary"),
        MANAGER("manager"),
        PUBLIC_MONITOR("public-monitor");

        private final String value;

        private Role(String value) {
            this.value = value;
        }

        @Override
        public String toString() {
            return value;
        }
    }

    @FXML private Button buttonS;
    @FXML private Button buttonM;
    @FXML private Button buttonPM;
    private LinkedList<Button> clientOptionButtons = new LinkedList<>();

    @FXML private TextField inputIP;

    private final static Pattern HOSTPORTPATTERN = Pattern.compile("^(?:\\d{1,3}\\.\\d{1,3}\\.\\d{1,3}\\.\\d{1,3}|localhost):[1-9]\\d*$");

    @Override
    public void initialize(URL location, ResourceBundle resources) {
        clientOptionButtons.add(buttonS);
        clientOptionButtons.add(buttonM);
        clientOptionButtons.add(buttonPM);
        clientOptionButtons.forEach(b -> b.setDisable(true));

        inputIP.textProperty().addListener((obs, o, n) -> {
            clientOptionButtons.forEach(b -> b.setDisable(!HOSTPORTPATTERN.matcher(n).find()));
        });

        inputIP.setText(App.lastIPPortGiven);
    }
    
    public void openSecretary() {
        connect(Role.SECRETARY, () -> {
            App.setRoot("secretary");
        });
    }
    
    public void openManager() {
        connect(Role.MANAGER, () -> {
            App.setRoot("manager");
        });
    }
    
    public void openPublicMonitor() {
        connect(Role.PUBLIC_MONITOR, () -> {
            App.setRoot("public-monitor");
        });
    }

    private void connect(Role asRole, Runnable onSuccessRun) {
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

            var fields = new HashMap<String, String>();
            fields.put("request", "subscribe");
            fields.put("role", asRole.toString());
            var requestSubscribe = new JSONObject(fields);
            
            App.NETWORK.send(Packet.encode(requestSubscribe));
            onSuccessRun.run();
        }
        else {
            App.Alerts.serverConnectionFailed();

            inputIP.setText(App.lastIPPortGiven);
            inputIP.setDisable(false);
            inputIP.requestFocus();
        }
    }

}
