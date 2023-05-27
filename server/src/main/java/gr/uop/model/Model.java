package gr.uop.model;

import java.io.FileInputStream;
import java.util.Scanner;

import org.json.simple.JSONObject;
import org.json.simple.parser.JSONParser;
import org.json.simple.parser.ParseException;

import gr.uop.network.Subscribers.Subscription;

public class Model {

    // TODO Consider instance backup.
    // Attempt to load from backup, then change initiate() to prefer backup if there is one than creating from scratch, also on shutdown create a backup of the current model.

    static public Model initiate() throws ModelException {
        return new Model();
    }

    private Model() throws ModelException {
        try (Scanner s = new Scanner(new FileInputStream("companies.csv"))) {
            s.nextLine(); // skip header line
            while(s.hasNextLine()) {
                var entry = s.nextLine().split(",");
                int companyID = Integer.parseInt(entry[0]);
                String companyName = entry[2];
                int tableNumber = Integer.parseInt(entry[1]);
                // TODO create them in the system
            }
        } catch (Exception e) {
            throw new ModelException(e.getMessage());
        }
        // TODO create empty user base
    }

    public void shutdown() {
        backupJSON();
    }

    // ===

    /**
     * 
     * @return basic info for the model like companies ids, their names, table numbers and image names
     */
    public JSONObject infoJSON() {
        try {
            // TODO (id == image name)
            String expecting = """
                    {
                        "companies":[
                            {
                                "id":0,
                                "image-name":0,
                                "name":"company name 0",
                                "table":0
                            },
                            {
                                "id":1,
                                "image-name":1,
                                "name":"company name 1",
                                "table":1
                            }
                        ]
                    }
                    """;
            return (JSONObject) new JSONParser().parse(expecting);
        } catch (ParseException e) {
            return new JSONObject();
        }
    }

    public JSONObject toJSONforSubscribersOf(Subscription subscription) {
        JSONObject json = null;
        switch (subscription) {
            case MANAGER:
                json = toJSONManager();
                break;
            case PUBLIC_MONITOR:
                json = toJSONPublicMonitor();
                break;
            case SECRETARY:
                json = new JSONObject(); // currently no data have to be sent
                break;
        }
        return json;
    }

    private JSONObject toJSONManager() {
        try {
            // TODO (public monitor + user queues)
            String expecting = """
                    {
                        "companies" : [
                            {
                                "id" : 0,
                                "state" : "calling",
                                "user-id" : 14,
                                "user-id-queue-waiting": [6,2,4],
                                "user-id-queue-unavailable": [12,4,9,6]
                            },
                            {
                                "id" : 5,
                                "state" : "calling-timeout",
                                "user-id" : 3,
                                "user-id-queue-waiting": [6,2,4],
                                "user-id-queue-unavailable": [12,4,9,6]
                            },
                            {
                                "id" : 12,
                                "state" : "occupied",
                                "user-id" : 9,
                                "user-id-queue-waiting": [6,2,4],
                                "user-id-queue-unavailable": [12,4,9,6]
                            },
                            {
                                "id" : 2,
                                "state" : "available",
                                "user-id" : -1,
                                "user-id-queue-waiting": [6,2,4],
                                "user-id-queue-unavailable": [12,4,9,6]
                            },
                            {
                                "id" : 7,
                                "state" : "paused",
                                "user-id" : -1,
                                "user-id-queue-waiting": [6,2,4],
                                "user-id-queue-unavailable": [12,4,9,6]
                            }
                        ]
                    }
                        """;
            return (JSONObject) new JSONParser().parse(expecting);
        } catch (ParseException e) {
            return new JSONObject();
        }
    }

    private JSONObject toJSONPublicMonitor() {
        try {
            // TODO
            String expecting = """
                        {
                            "companies" : [
                                {
                                    "id" : 0,
                                    "state" : "calling",
                                    "user-id" : 14
                                },
                                {
                                    "id" : 5,
                                    "state" : "calling-timeout",
                                    "user-id" : 3
                                },
                                {
                                    "id" : 12,
                                    "state" : "occupied",
                                    "user-id" : 9
                                },
                                {
                                    "id" : 2,
                                    "state" : "available",
                                    "user-id" : -1
                                },
                                {
                                    "id" : 7,
                                    "state" : "paused",
                                    "user-id" : -1
                                }
                            ]
                        }
                    """;
            return (JSONObject) new JSONParser().parse(expecting);
        } catch (ParseException e) {
            return new JSONObject();
        }
    }

    private void backupJSON() {
        // TODO save a JSON human readable backup file
    }

}
