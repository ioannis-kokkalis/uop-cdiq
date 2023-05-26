module gr.uop {
    requires transitive javafx.controls;
    requires javafx.fxml;
    requires transitive json.simple;
    
    opens gr.uop to javafx.fxml;
    exports gr.uop;
}
