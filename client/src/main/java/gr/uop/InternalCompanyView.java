package gr.uop;

import gr.uop.ControllerSecretary.Media;
import javafx.geometry.Insets;
import javafx.geometry.Pos;
import javafx.scene.control.Label;
import javafx.scene.image.Image;
import javafx.scene.image.ImageView;
import javafx.scene.layout.Pane;
import javafx.scene.layout.VBox;

public class InternalCompanyView extends VBox{
    private String id;
    private ImageView logoContainer;
    private Label name = new Label();
    private Pane status = new Pane();

    public InternalCompanyView(String logoUrl , String companyName,Media list, String companyId){
        id = companyId;
        logoContainer = new ImageView(new Image( App.class.getResourceAsStream(logoUrl)));
        logoContainer.setFitWidth(165);
        logoContainer.setFitHeight(110);

        name.setText(companyName);

        status.setPadding(new Insets(10, 80,10,80));
        status.getStyleClass().add("color-background-notselected");

        getChildren().addAll(logoContainer,name,status);
        setAlignment(Pos.CENTER);
        getStyleClass().add("gaping-h-much");
        getStyleClass().add("border");
        
        setOnMouseClicked(e -> {
            if (status.getStyleClass().get(0).equals("color-background-notselected")){
                status.getStyleClass().remove("color-background-notselected");
                status.getStyleClass().add("color-background-selected");
                list.addSelectedCompanies(this);
            }
            else{
                status.getStyleClass().remove("color-background-selected");
                status.getStyleClass().add("color-background-notselected");
                list.removeSelectedCompanies(this);
            }
        });
    }

    public String getCompanyId(){
        return id;
    }

    public void resetStatus(){
        status.getStyleClass().remove(0);
        status.getStyleClass().add("color-background-notselected");
    }


    public String toString(){
        return name.getText();
    }

    


}
