package gr.uop;

import java.time.LocalDateTime;
import java.time.format.DateTimeFormatter;
import java.util.LinkedList;

import org.json.simple.JSONArray;
import org.json.simple.JSONObject;

import gr.uop.Network.Packet;
import javafx.animation.AnimationTimer;
import javafx.animation.KeyFrame;
import javafx.animation.Timeline;
import javafx.application.Platform;
import javafx.geometry.Insets;
import javafx.geometry.Pos;
import javafx.scene.control.Label;
import javafx.scene.image.Image;
import javafx.scene.image.ImageView;
import javafx.scene.layout.HBox;
import javafx.scene.layout.Priority;
import javafx.scene.layout.VBox;
import javafx.util.Duration;

public class ExternalCompanyView extends VBox{
    private HBox logoInfoContainer = new HBox();
    private ImageView logoContainer;
    private VBox infoContainer = new VBox();
    private Label callNumber = new Label();
    private Label remainTime = new Label();
    private Label name = new Label();
    private String id;
    private JSONArray waitingQueue= new JSONArray(),unavailiableQueue= new JSONArray(); // manager specific
    private String userId;
    private String state;
    Timeline timer;

    public ExternalCompanyView(String logoUrl , String companyName){
        logoContainer = new ImageView(new Image( App.class.getResourceAsStream(logoUrl)));
        logoContainer.setFitWidth(153);
        logoContainer.setFitHeight(102);

        setAvailiable();
        callNumber.getStyleClass().add("l-color");
        remainTime.getStyleClass().add("l-color");
    
        infoContainer.getChildren().addAll(callNumber,remainTime);
        infoContainer.setAlignment(Pos.CENTER);
        infoContainer.setPadding(new Insets(0, 10,0,10));

        logoInfoContainer.getChildren().addAll(logoContainer,infoContainer);
        HBox.setHgrow(infoContainer, Priority.ALWAYS);

        name.setText(companyName);
        
      
        getChildren().addAll(logoInfoContainer,name);
        setAlignment(Pos.CENTER);
        
        getStyleClass().add("gaping-h-much");
        getStyleClass().add("border");
    }

    public ExternalCompanyView(String logoUrl , String companyName,String companyid, String tableId){
        this(logoUrl,companyName);
        id = companyid;
        Label table = new Label(tableId);
        getChildren().add(0, table);

        logoContainer.setOnMouseClicked(e -> {
            App.Alerts.managerViewQueue(name.getText(),waitingQueue, unavailiableQueue);
        });

        infoContainer.setOnMouseClicked(e -> {
            String choice = chooseAlert();
            if(choice!=null){
                JSONObject map = new JSONObject();
            
                map.put("request", "manager");
                map.put("action",choice);
                map.put("company-id",id);
                map.put("company-state",state);

                App.NETWORK.send(Packet.encode(new JSONObject(map)));
                App.scene.getRoot().setDisable(true);
            }
        });
    }

    public String chooseAlert(){
        if(state.equals("calling"))
            return App.Alerts.managerCallingState(userId);
        else if (state.equals("paused"))
            return  App.Alerts.managerPauseState(userId);
        else if(state.equals("occupied"))
            return App.Alerts.managerOcuppiedState(userId);
        else if(state.equals("available"))
            return App.Alerts.managerAvailiableState();
        else
            return App.Alerts.managerFrozenState(userId);
    }

    public void setWaitingQueue(JSONArray queue){
        waitingQueue = queue;
        System.out.println(waitingQueue);
    }

    public void setUnavailiableQueue(JSONArray queue){
        unavailiableQueue = queue;
    }

    public void setAvailiable(){
        state = "availiable";
        if(infoContainer.getStyleClass().size()>0)
            infoContainer.getStyleClass().remove(0);
        infoContainer.getStyleClass().add("color-background-available");
        remainTime.setText("");
        remainTime.setManaged(false);
        callNumber.setText("Availiable");
    }

    public void setCalling(String userid,int time){
        state = "calling";
        userId = userid;
        infoContainer.getStyleClass().remove(0);
        infoContainer.getStyleClass().add("color-background-calling");
        callNumber.setText("Calling: "+userid);
        remainTime.setManaged(true);
        
        DurationWrapper remainingTime = new DurationWrapper(Duration.seconds(time));

        timer = new Timeline(
            new KeyFrame(Duration.seconds(0), e -> {
            
                remainingTime.value = remainingTime.value.add(Duration.seconds(1));

                int minutes = (int) remainingTime.value.toMinutes();
                int seconds = (int) (remainingTime.value.toSeconds() % 60);
                String formattedTime = String.format("%02d:%02d", minutes, seconds);

                remainTime.setText(formattedTime);
            }),
            new KeyFrame(Duration.seconds(1))
        );
        timer.setCycleCount(Timeline.INDEFINITE);

        timer.playFromStart();

       // remainTime.setText("Remaining: XX:YY");

    }

    public void setPaused(){
        state = "paused";
        if(timer!=null)
            timer.stop();
        infoContainer.getStyleClass().remove(0);
        infoContainer.getStyleClass().add("color-background-paused-frozen");
        callNumber.setText("Paused");
        remainTime.setManaged(false);
        remainTime.setText("");
    }

    public void setFrozen(String userid){
        state = "calling-timeout";
        userId = userid;
        if(timer!=null)
            timer.stop();
        infoContainer.getStyleClass().remove(0);
        infoContainer.getStyleClass().add("color-background-paused-frozen");
        callNumber.setText("Frozen: "+userid);
        remainTime.setManaged(false);
        remainTime.setText("");
    }

    public void setOccupied(String userid){
        state = "ocupied";
        userId = userid;
        if(timer!=null)
            timer.stop();
        infoContainer.getStyleClass().remove(0);
        infoContainer.getStyleClass().add("color-background-occupied");
        callNumber.setText("Ocuppied: "+userid);
        remainTime.setManaged(false);
        remainTime.setText("");
    }

    class DurationWrapper {
        Duration value;
        public DurationWrapper(Duration value) {
            this.value = value;
        }
    }
    
}
