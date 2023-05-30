package gr.uop;

import java.net.URL;
import java.util.ArrayList;
import java.util.HashMap;
import java.util.LinkedList;
import java.util.ResourceBundle;

import org.json.simple.JSONArray;

import javafx.application.Platform;
import javafx.fxml.FXML;
import javafx.scene.layout.TilePane;

public class ControllerManager extends BaseController {
    
    @FXML TilePane companiesContainer;

    String logoUrls = "media/company-logo/";
    
    HashMap<String,ExternalCompanyView> companyBlocks = new HashMap<>();
    
    @Override
    public void initialize(URL location, ResourceBundle resources) {
        App.CONTROLLER = this;
        super.initialize(location, resources);
    }

    public void setEnvironment(ArrayList<Object>[] data){
        Platform.runLater(()->{
            for (int i = 0; i < data.length; i++) {
                ExternalCompanyView company =  new ExternalCompanyView(logoUrls+""+data[i].get(0)+".png", data[i].get(1)+"",data[i].get(2)+"",data[i].get(3)+"");
                companiesContainer.getChildren().add(company);
                companyBlocks.put(data[i].get(2)+"",company);
            }
        });
    }

    public void updateEnvironment( ArrayList<Object>[] data){
        Platform.runLater(()->{
            for (int i = 0; i < data.length; i++) {
                ExternalCompanyView company =  companyBlocks.get(data[i].get(1)+"");
                String state = data[i].get(2)+"";
                if(state.equals("available"))
                    company.setAvailiable();
                else if(state.equals("calling"))
                    company.setCalling(data[i].get(0)+"",Integer.parseInt(data[i].get(3)+""));
                else if(state.equals("occupied"))
                    company.setOccupied(data[i].get(0)+"");
                else if(state.equals("paused"))
                    company.setPaused();
                else
                    company.setFrozen(data[i].get(0)+"");
                
                
                JSONArray unavailiableQueue = (JSONArray)data[i].get(3);
                JSONArray waitingQueue = (JSONArray)data[i].get(4);
                
                company.setUnavailiableQueue(unavailiableQueue);
                company.setWaitingQueue(waitingQueue);
            }
        });
    }

    public void informUser(String answer){
        Platform.runLater(()->{
            if(answer.equals("ok")){
                App.Alerts.InformUser("Succesful Change", "Action has been completed succesfully");
            }
            else
                App.Alerts.InformUser("Unsuccesful Change", "There was an error with our action please try again later");
            App.scene.getRoot().setDisable(false);
        });
    }

}
