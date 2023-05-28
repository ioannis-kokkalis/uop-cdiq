package gr.uop.model;

import java.io.FileInputStream;
import java.util.Collection;
import java.util.HashMap;
import java.util.LinkedHashMap;
import java.util.Map;
import java.util.Scanner;

import org.json.simple.JSONArray;
import org.json.simple.JSONObject;
import org.json.simple.parser.JSONParser;
import org.json.simple.parser.ParseException;

import gr.uop.model.Company.State;
import gr.uop.network.Subscribers.Subscription;

public class Model {

    // TODO Consider instance backup.
    // Attempt to load from backup, then change initiate() to prefer backup if there is one than creating from scratch, also on shutdown create a backup of the current model.

    // DOING companies and users classes / logic

    private class UsersManager {

        private final Map<Integer, User> users;

        public UsersManager() {
            this.users = new LinkedHashMap<>();
        }

        public User attemptGetUser(int id, String name, String secret) {
            User user = users.get(id);

            if( user != null )
                return user;
            
            // TODO needs improvement
            // BUG relative weak searching, may cause issues on details
            for (User useri : this.users.values()) {
                if( name.toLowerCase().equals(useri.getName().toLowerCase()) ||
                        secret.equals(useri.getSecret()) ) {
                    user = useri;
                    break;
                }
            }

            return user;
        }

        public void add(User user) {
            this.users.put(user.getID(), user);
        }

        @Override
        public String toString() {
            var sb = new StringBuilder();
            users.forEach((uID, u) -> {
                sb.append(u).append("\n");
            });
            sb.deleteCharAt(sb.length()-1);
            return sb.toString();
        }

    }

    public class CompaniesManager {

        private final Map<Integer, Company> companies;

        // TODO keep affected companies from last change, network will send those if needed not all each time

        public CompaniesManager() {
            this.companies = new LinkedHashMap<>();
        }

        public Collection<Company> getAll() {
            return companies.values();
        }

        public void add(Company company) {
            this.companies.put(company.getID(), company);
        }

        @Override
        public String toString() {
            var sb = new StringBuilder();
            companies.forEach((cID, c) -> {
                sb.append(c).append("\n");
            });
            sb.deleteCharAt(sb.length()-1);
            return sb.toString();
        }

    }

    private final UsersManager usersManager;
    private final CompaniesManager companiesManager;

    static public Model initiate() throws ModelException {
        return new Model();
    }

    private Model() throws ModelException {
        this.usersManager = new UsersManager();
        this.companiesManager = new CompaniesManager();

        try (Scanner s = new Scanner(new FileInputStream("companies.csv"))) {
            s.nextLine(); // skip header line
            while(s.hasNextLine()) {
                var entry = s.nextLine().split(",");
                int companyID = Integer.parseInt(entry[0]);
                String companyName = entry[2];
                int tableNumber = Integer.parseInt(entry[1]);
                
                var company = new Company(companyID, companyName, tableNumber);
                fakeDifferentStates(company);
                companiesManager.add(company);
            }
        } catch (Exception e) {
            throw new ModelException(e.getMessage());
        }

        System.out.println();
        System.out.println("=== (Companies) ===");
        System.out.println(companiesManager);
        System.out.println("===");
        System.out.println();
    }

    private void fakeDifferentStates(Company company) {
        // TODO delete method after testing
        if( company.getID() == 3 ) {
            company.setState(State.CALLING);
            company.getState().setUser(new User("A", "secretB"));
        }
        else if( company.getID() == 6 ) {
            company.setState(State.CALLING_TIMEOUT);
            company.getState().setUser(new User("X", "secretY"));
        }
        else if( company.getID() == 9 ) {
            company.setState(State.OCCUPIED);
            company.getState().setUser(new User("R", "secretT"));
        }
        else if( company.getID() == 12 )
            company.setState(State.PAUSED);
    }

    public void shutdown() {
        backupJSON();
    }

    // ===

    /**
     * @return {@code null} when unable to find user, user does not exist (or failed to find)
     */
    public User getUser(int id, String name, String secret) {
        return usersManager.attemptGetUser(id, name, secret);
    }

    /**
     * 
     * @return basic info for the model like companies ids, their names, table numbers and image names
     */
    public JSONObject infoJSON() {
        var arr = new JSONArray();
        companiesManager.getAll().forEach(company -> {
            var map = new HashMap<String, String>();
            
            map.put("id", "" + company.getID());
            map.put("image-name", "" + company.getID());
            map.put("name", "" + company.getName());
            map.put("table", "" + company.getTableNumber());

            arr.add(new JSONObject(map));
        });

        var map = new HashMap<String, JSONArray>(Map.of("companies", arr));
        return new JSONObject(map);
    }

    public JSONObject toJSONforSubscribersOf(Subscription subscription, boolean onlyChanged) {
        JSONObject json = null;
        switch (subscription) {
            case MANAGER:
                json = toJSONManager(onlyChanged);
                break;
            case PUBLIC_MONITOR:
                json = toJSONPublicMonitor(onlyChanged);
                break;
            case SECRETARY:
                json = new JSONObject(); // currently no data have to be sent
                break;
        }
        return json;
    }

    private JSONObject toJSONManager(boolean onlyChanged) {
        try {
            // TODO (public monitor + user queues) + convert values to strings and to arrays of strings
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

    private JSONObject toJSONPublicMonitor(boolean onlyChanged) {
        JSONArray arr = new JSONArray();

        if (onlyChanged) {

        } else /* contain all companies */ {
            companiesManager.getAll().forEach(company -> {
                var map = new HashMap<String, String>();
                var state = company.getState();
                var user = state.getUser();

                map.put("id", "" + company.getID());
                map.put("state", "" + company.getState().toString());
                map.put("user-id", "" + (user != null ? user.getID() : -1));
                // TODO send time as well if state has timer. In case that a timer has started but a new client connected, the timer must go on from the already timer not from 00:00. In cases that the state just changed, send timer 0 to keep it uniform in all cases.

                arr.add(new JSONObject(map));
            });
        }

        var map = new HashMap<String, JSONArray>(Map.of("companies", arr));
        return new JSONObject(map);
    }

    private void backupJSON() {
        // TODO save a JSON human readable backup file
    }

}
