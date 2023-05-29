package gr.uop.network;

import org.json.simple.JSONObject;
import org.json.simple.parser.JSONParser;
import org.json.simple.parser.ParseException;

import gr.uop.App;

public class Packet {
    
    public static String encode(JSONObject message) {
        return message.toJSONString();
    }

    public static JSONObject decode(String message) {
        JSONObject decoded = null;

        try {
            decoded = (JSONObject) new JSONParser().parse(message);
        } catch (ParseException e) {
            App.consoleLogError("Failed to decode client message.", message);
        }

        return decoded;
    }

}
