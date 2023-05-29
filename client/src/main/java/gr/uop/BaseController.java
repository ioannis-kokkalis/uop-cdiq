package gr.uop;
import java.net.URL;
import java.util.ResourceBundle;

import javafx.application.Platform;
import javafx.fxml.Initializable;

public class BaseController implements Initializable{

    @Override
    public void initialize(URL location, ResourceBundle resources) {
        Platform.runLater(()->{
            App.smp.release();
        });
    }

}
