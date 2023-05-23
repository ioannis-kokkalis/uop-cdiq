package gr.uop;

import java.io.FileOutputStream;
import java.io.IOException;
import java.io.PrintWriter;
import java.net.URL;
import java.text.SimpleDateFormat;
import java.util.Date;
import java.util.ResourceBundle;

import javafx.application.Platform;
import javafx.fxml.FXML;
import javafx.fxml.Initializable;
import javafx.scene.control.TextArea;

public class Controller implements Initializable {

    private final static String EVENT_START = "-> ";
    private final static String EVENT_END = "\n";

    @FXML private TextArea log;

    @Override
    public void initialize(URL location, ResourceBundle resources) {
        App.LOGGER = this;
        
        log.setText("Logging:\n");
    }

    public void updateLog(String eventDescription) {
        var logging = new StringBuilder(new SimpleDateFormat("HH:mm:ss").format(new Date()))
            .append(" ")
            .append(EVENT_START)
            .append(eventDescription)
            .append(EVENT_END)
            .toString();

        Platform.runLater(() -> { log.appendText(logging); });

        // TODO rework logging
        // Add file logging
        // Add external device logging?
        // Logger interface and create a central logger in app that will call logging to the rest?
    }

}
