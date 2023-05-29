package gr.uop;

import java.net.URL;
import java.security.cert.PKIXCertPathChecker;
import java.util.ArrayList;
import java.util.HashMap;
import java.util.LinkedList;
import java.util.ResourceBundle;

import org.json.simple.JSONArray;
import org.json.simple.JSONObject;

import gr.uop.Network.Packet;
import javafx.application.Platform;
import javafx.fxml.FXML;
import javafx.fxml.Initializable;
import javafx.scene.control.Alert;
import javafx.scene.control.Button;
import javafx.scene.control.TextField;
import javafx.scene.control.Alert.AlertType;
import javafx.scene.image.ImageView;
import javafx.scene.layout.TilePane;
import javafx.stage.Modality;


public class ControllerSecretary extends BaseController {

    @FXML TextField inputName;
    @FXML TextField inputId;
    @FXML TextField inputUserId;

    @FXML TilePane companiesContainer;

    @FXML Button buttonClear;
    @FXML Button buttonInsert;
    @FXML Button buttonSearch;

    @FXML ImageView test;

    Media list;

    String logoUrls = "media/company-logo/";

    @Override
    public void initialize(URL location, ResourceBundle resources) {
        App.CONTROLLER = this;
        super.initialize(location, resources);
    }

    public void setEnvironment(ArrayList<Object>[] data){
        list = new Media();
        Platform.runLater(()->{
            for (int i = 0; i < data.length; i++) {
                InternalCompanyView company =  new InternalCompanyView(logoUrls+""+data[i].get(0)+".png", data[i].get(1)+"",list,data[i].get(2)+"");
                companiesContainer.getChildren().add(company);
            }
        });
    }

    public void informSearch(ArrayList<Object>[] data){
        Platform.runLater(()->{
            String mainText,headerText;

            if(data[0].get(0).equals("found")){
                headerText = "User found succesfully";
                mainText = "User with systemId "+data[2].get(0)+" name "+data[1].get(0)+" and id "+data[3].get(0)+" was found and is registered in companies: "+data[4].toString();
            }
            else{
                headerText = "User not found";
                mainText = "User with systemId "+data[2].get(0)+" name "+data[1].get(0)+" and id "+data[3].get(0)+" was not found";
            }
            App.Alerts.secretaryInformUser(mainText, headerText);
            App.scene.getRoot().setDisable(false);
        });
    }

    public void informRegister(ArrayList<Object>[] data){
        Platform.runLater(()->{
                
            String headerText = "User was successfully inserted with code: "+data[2].get(0);
            String mainText = "User with name "+ data[1].get(0) +" and id "+data[3].get(0); 

            App.Alerts.secretaryInformUser(mainText, headerText);
            App.scene.getRoot().setDisable(false);
        });

    }

    public void informInsert(ArrayList<Object>[] data){
        Platform.runLater(()->{
            String mainText,headerText;

            if(data[0].get(0).equals("ok")){
                headerText = "User was succesfully inserted in companies";
                mainText = "User with systemid "+data[2].get(0)+" name "+data[1].get(0)+" and id "+data[3].get(0)+" and has registered in companies: "+data[4].toString();
            }
            else{
                headerText = "User not found";
                mainText = "User with systemid "+data[2].get(0)+" name "+data[1].get(0)+" and id "+data[3].get(0)+" was not found";
            }

            App.Alerts.secretaryInformUser(mainText, headerText);
            App.scene.getRoot().setDisable(false);
        });

    }


    @FXML
    public void clear(){
        inputName.setText("");
        inputId.setText("");
        inputUserId.setText("");
        list.clearSelectedCompanies();
    }

    @FXML
    public void insert(){
        JSONObject map;

        if((inputUserId.getText().isEmpty())&&(inputName.getText().isEmpty()||inputId.getText().isEmpty())){
            App.Alerts.secretaryIllegalSearch("Please fill either userid or name and id to complete the insert");
            return;
        }
        else if(!inputUserId.getText().isEmpty()) {
            map = new JSONObject();
            
            map.put("request", "user-insert");
            map.put("id",inputUserId.getText());
            
            var ar = new JSONArray();

            for (InternalCompanyView element : list.getSelectedCompanies()) {
                ar.add(element.getCompanyId());
            }

            map.put("companies-to-register", ar);
            
        } 
        else {
            map = new JSONObject();
            
            map.put("request", "user-register");
            map.put("name",inputName.getText());
            map.put("secret",inputId.getText());
            
            var ar = new JSONArray();

            for (InternalCompanyView element : list.getSelectedCompanies()) {
                ar.add(element.getCompanyId());
            }

            map.put("companies-to-register", ar);
            
        }

        if(App.Alerts.secretaryConfirmInsert().equals("OK")){
            App.NETWORK.send(Packet.encode(new JSONObject(map)));
            App.scene.getRoot().setDisable(true);
        }
        else
            clear();
        
    }

    @FXML
    public void search(){

        if((inputUserId.getText().isEmpty()) && (inputName.getText().isEmpty()||inputId.getText().isEmpty())){
            App.Alerts.secretaryIllegalSearch("Please fill either userid or name and id to complete the search");
        }
        else{
            var map = new HashMap<String, String>();
            
            map.put("request", "user-info");
            map.put("id",inputUserId.getText());
            map.put("name", inputName.getText());
            map.put("secret", inputId.getText());

            App.NETWORK.send(Packet.encode(new JSONObject(map)));
            App.scene.getRoot().setDisable(true);
        }
        
    }
    
    public class Media {
        private LinkedList<InternalCompanyView> selectedCompanies = new LinkedList<>();
    
        
        public LinkedList<InternalCompanyView> getSelectedCompanies(){
            return selectedCompanies;
        }
    
        public void addSelectedCompanies(InternalCompanyView element){
            selectedCompanies.add(element);
        }
        
        public void removeSelectedCompanies(InternalCompanyView element){
            selectedCompanies.remove(element);
        }
    
        public void clearSelectedCompanies(){
            for (InternalCompanyView companyView : selectedCompanies) {
                companyView.resetStatus();
            }
            selectedCompanies.clear();
        }
    }
}

