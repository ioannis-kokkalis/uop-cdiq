package gr.uop.model;

import java.io.FileInputStream;
import java.util.HashMap;
import java.util.Map;
import java.util.Scanner;

import org.json.simple.JSONObject;

import gr.uop.network.Subscribers.Subscription;

public class Model {

    // TODO Consider instance backup.
    // Attempt to load from backup, then change initiate() to prefer backup if there is one than creating from scratch, also on shutdown create a backup of the current model.

    static public Model initiate() throws ModelException {
        return new Model();
    }

    private Model() throws ModelException {
        try (Scanner s = new Scanner(new FileInputStream("companies.txt"))) {
            // TODO load companies
            // while(s.hasNextLine()) {
            //     System.out.println(s.nextLine());
            // }
        } catch (Exception e) {
            throw new ModelException(e.getMessage());
        }
    }

    public void shutdown() {
        // TODO store a human readable JSON
    }

    // ===

    public JSONObject toJSON(Subscription forSubscribersOf) {
        JSONObject json = null;
        switch (forSubscribersOf) {
            case MANAGER:
                json = toJSONManager();
                break;
            case PUBLIC_MONITOR:
                json = toJSONPublicMonitor();
                break;
            case SECRETARY:
                json = toJSONSecretary();
                break;
        }
        return json;
    }

    private JSONObject toJSONSecretary() {
        return new JSONObject(new HashMap<String, String>(Map.of("dataFor","secretary"))); // TODO
    }

    private JSONObject toJSONManager() {
        return new JSONObject(new HashMap<String, String>(Map.of("dataFor","manager"))); // TODO
    }

    private JSONObject toJSONPublicMonitor() {
        return new JSONObject(new HashMap<String, String>(Map.of("dataFor","public-monitor"))); // TODO
    }

}
