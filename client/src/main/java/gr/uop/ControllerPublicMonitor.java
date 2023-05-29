package gr.uop;

import java.net.URL;
import java.util.ArrayList;
import java.util.HashMap;
import java.util.ResourceBundle;

import javafx.application.Platform;
import javafx.fxml.FXML;
import javafx.fxml.Initializable;
import javafx.scene.layout.TilePane;

public class ControllerPublicMonitor extends BaseController {
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
                ExternalCompanyView company =  new ExternalCompanyView(logoUrls+""+data[i].get(0)+".png", data[i].get(1)+"");
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
                System.out.println(state);
                if(state.equals("available"))
                    company.setAvailiable();
                else if(state.equals("calling"))
                    company.setCalling(data[i].get(0)+"");
                else if(state.equals("occupied"))
                    company.setOccupied(data[i].get(0)+"");
                else if(state.equals("paused"))
                    company.setPaused();
                else
                    company.setFrozen(data[i].get(0)+"");
            }
        });
    }
}
