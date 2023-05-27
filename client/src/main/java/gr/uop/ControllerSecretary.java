package gr.uop;

import java.io.IOException;
import java.net.URL;
import java.util.ResourceBundle;

import javafx.fxml.FXML;
import javafx.fxml.Initializable;
import javafx.scene.control.Button;
import javafx.scene.control.TextField;
import javafx.scene.layout.TilePane;
import javafx.scene.layout.VBox;
import javafx.scene.shape.Rectangle;


public class ControllerSecretary implements Initializable {

    @FXML TextField inputName;
    @FXML TextField inputId;

    @FXML TilePane companiesContainer;

    @FXML Button buttonClear;
    @FXML Button buttonInsert;

    @Override
    public void initialize(URL location, ResourceBundle resources) {
        for (int i = 0; i < 18; i++) {
            companiesContainer.getChildren().add(i, new Rectangle(300, 100));
        }
        
    }

    @FXML
    public void clear(){
        
    }

    @FXML
    public void insert(){
        
    }
    

}
