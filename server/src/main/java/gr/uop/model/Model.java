package gr.uop.model;

import java.io.FileInputStream;
import java.io.FileWriter;
import java.io.IOException;
import java.nio.file.Files;
import java.nio.file.Path;
import java.nio.file.StandardCopyOption;
import java.time.LocalDateTime;
import java.time.format.DateTimeFormatter;
import java.util.Collection;
import java.util.HashMap;
import java.util.LinkedHashMap;
import java.util.Map;
import java.util.Scanner;

import org.json.simple.JSONArray;
import org.json.simple.JSONObject;

import gr.uop.App;
import gr.uop.model.Company.State;
import gr.uop.model.User.Status;
import gr.uop.network.Subscribers.Subscription;

public class Model {

    // TODO Consider instance backup.
    // Attempt to load from backup, then change initiate() to prefer backup if there is one than creating from scratch, also on shutdown create a backup of the current model.

    private class UsersManager {

        private final Map<Integer, User> users;

        public UsersManager() {
            this.users = new LinkedHashMap<>();
        }

        public Collection<User> getAll() {
            return users.values();
        }

        public User attemptGetUser(int id, String name, String secret) {
            User user = users.get(id);

            if( user != null )
                return user;
            
            // TODO needs improvement
            // BUG relative weak searching, may cause issues on details
            for (User useri : this.users.values()) {
                if( secret.equals(useri.getSecret()) ) {
                    user = useri;
                    break;
                }
            }

            if(user != null)
                return user;

            for (User useri : this.users.values()) {
                if( name.toLowerCase().equals(useri.getName().toLowerCase()) ) {
                    user = useri;
                    break;
                }
            }

            if(user != null)
                return user;

            return null;
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

        // TODO (late game) keep affected companies from last change, network will send those if needed not all each time

        public CompaniesManager() {
            this.companies = new LinkedHashMap<>();
        }

        public Company get(int companyID) {
            return companies.get(companyID);
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
                companiesManager.add(company);
            }
        } catch (Exception e) {
            throw new ModelException(e.getMessage());
        }

        App.consoleLog("Companies", companiesManager.toString());
    }

    public void shutdown() {
        backupJSON();
    }

    // ===

    public enum ManagerAction {
        ARRIVED("arrived"),
        DISCARD("discard"),
        COMPLETED("completed"),
        COMPLETED_PAUSE("completed-pause"),
        PAUSE("pause"),
        RESUME("resume");

        public final String value;

        private ManagerAction(String value) {
            this.value = value;
        }

        @Override
        public String toString() {
            return value;
        }
    }

    public enum Action {
        USER_REGISTER_IN_COMPANIES,
        CALLING_TIMEDOUT,

        MANAGER_AVAILABLE_PAUSE,
        MANAGER_PAUSED_RESUME,
        MANAGER_CALLING_ARRIVED,
        MANAGER_CALLING_PAUSE,
        MANAGER_CALLING_DISCARD,
        MANAGER_CALLINGTIMEOUT_ARRIVED,
        MANAGER_CALLINGTIMEOUT_DISCARD,
        MANAGER_OCCUPIED_COMPLETED,
        MANAGER_OCCUPIED_COMPLETEDPAUSE
    }

    /**
     * Model may have been changed after this method completes.
     * This method should be <b>only called as part of a {@link gr.uop.Task#run()} method</b> to avoid sharing data collisions.
     * @param user if the action requires, else null (check switch for specifics)
     * @param companyIDs if the action requires, else skip the parameter
     */
    public void handleAction(Action action, User user, int... companyIDs) {
        boolean didAction = false;

        switch (action) {
            case USER_REGISTER_IN_COMPANIES:
                didAction = companyIDs.length > 0 && user != null;
                if (!didAction) {
                    App.consoleLogError("Can't handle \"" + action + "\".",
                            "Requires more than 0 companies and user not null.");
                    break;
                }

                // ===

                for (int companyID : companyIDs)
                    getCompany(companyID).add(user);

                // ===

                break;

            case CALLING_TIMEDOUT:
                didAction = companyIDs.length == 1 && user == null;
                if (!didAction) {
                    App.consoleLogError("Can't handle \"" + action + "\".",
                        "Requires exactly 1 company and null user.");
                    break;
                }

                // ===

                var company = getCompany(companyIDs[0]);
                user = company.getStateUser();
                company.setState(State.CALLING_TIMEOUT);
                company.setStateUser(user);

                // ===

                break;
                
            case MANAGER_AVAILABLE_PAUSE:
                if (validArgumentsForActionsThatManagerTriggers(action, user, companyIDs))
                    break;
                
                // ===
                
                // CHECK
                company = getCompany(companyIDs[0]);

                company.setState(State.PAUSED);

                didAction = true;

                // ===

                break;
            case MANAGER_CALLING_ARRIVED:
            case MANAGER_CALLINGTIMEOUT_ARRIVED:
                if (validArgumentsForActionsThatManagerTriggers(action, user, companyIDs))
                    break;

                // ===
                
                // CHECK
                company = getCompany(companyIDs[0]);
                user = company.getStateUser();

                company.setState(State.OCCUPIED);
                company.setStateUser(user);

                user.isNow(Status.INTERVIEW);

                didAction = true;

                // ===

                break;
            case MANAGER_CALLING_DISCARD:
            case MANAGER_CALLINGTIMEOUT_DISCARD:
            case MANAGER_OCCUPIED_COMPLETED:
                if (validArgumentsForActionsThatManagerTriggers(action, user, companyIDs))
                    break;

                // ===
                
                // CHECK
                company = getCompany(companyIDs[0]);
                user = company.getStateUser();

                company.remove(user);
                company.setState(State.AVAILABLE);

                user.getCompaniesRegisteredAt().remove(company);
                user.isNow(Status.WAITING);

                didAction = true;
                
                // ===

                break;
            case MANAGER_CALLING_PAUSE:
                if (validArgumentsForActionsThatManagerTriggers(action, user, companyIDs))
                    break;

                // ===
                
                // CHECK
                company = getCompany(companyIDs[0]);
                user = company.getStateUser();

                company.setState(State.PAUSED);
                
                user.isNow(Status.WAITING);

                didAction = true;
                
                // ===

                break;
            case MANAGER_OCCUPIED_COMPLETEDPAUSE:
                if (validArgumentsForActionsThatManagerTriggers(action, user, companyIDs))
                    break;

                // ===
                
                // CHECK
                company = getCompany(companyIDs[0]);
                user = company.getStateUser();

                company.remove(user);
                company.setState(State.PAUSED);

                user.getCompaniesRegisteredAt().remove(company);
                user.isNow(Status.WAITING);
                
                didAction = true;

                // ===

                break;
            case MANAGER_PAUSED_RESUME:
                if (validArgumentsForActionsThatManagerTriggers(action, user, companyIDs))
                    break;

                // ===
                
                // CHECK
                company = getCompany(companyIDs[0]);

                company.setState(State.AVAILABLE);

                didAction = true;

                // ===

                break;
        }

        if (didAction) {
            this.companiesManager.getAll().forEach(c -> {
                c.update(this);
            });
            this.backupJSON();
            App.network.updateSubscribers(Subscription.PUBLIC_MONITOR, Subscription.MANAGER);
        }
    }

    private boolean validArgumentsForActionsThatManagerTriggers(Action action, User user, int[] companyIDs) {
        boolean result = companyIDs.length != 1 || user != null;
        if(result) {
            App.consoleLogError("Can't handle \"" + action + "\".",
                "Requires exactly 1 company and null user.");
        }
        return result;
    }

    public User createUser(String name, String secret) {
        var user = new User(name, secret);

        usersManager.add(user);

        return user;
    }

    /**
     * @return {@code null} when unable to find user, user does not exist (or failed to find)
     */
    public User getUser(int id, String name, String secret) {
        return usersManager.attemptGetUser(id, name, secret);
    }

    /**
     * @return {@code null} when unable to find company
     */
    public Company getCompany(int id) {
        return companiesManager.get(id);
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
        JSONArray arr = new JSONArray();

        if (onlyChanged) {

        } else /* contain all companies */ {
            companiesManager.getAll().forEach(company -> {
                var json = new JSONObject();
                var state = company.getState();
                var user = company.getStateUser();

                json.put("id", "" + company.getID());
                json.put("state", "" + company.getState().toString());
                if (state.equals(State.CALLING))
                    json.put("elapsed-in-sec", "" + company.getStateCountdown().elapsed());
                json.put("user-id", "" + (user != null ? user.getID() : -1));

                var qWaiting = new JSONArray();
                var qUnavailable = new JSONArray();
                for (User iUser : company.getUnmodifiableQueue()) {
                    if (iUser.is(Status.WAITING))
                        qWaiting.add(iUser.getID());
                    else
                        qUnavailable.add(iUser.getID());
                }
                json.put("user-id-queue-waiting", qWaiting);
                json.put("user-id-queue-unavailable", qUnavailable);

                arr.add(json);
            });
        }

        var map = new HashMap<String, JSONArray>(Map.of("companies", arr));
        return new JSONObject(map);
    }

    private JSONObject toJSONPublicMonitor(boolean onlyChanged) {
        JSONArray arr = new JSONArray();

        if (onlyChanged) {

        } else /* contain all companies */ {
            companiesManager.getAll().forEach(company -> {
                var map = new HashMap<String, String>();
                var state = company.getState();
                var user = company.getStateUser();

                map.put("id", "" + company.getID());
                map.put("state", "" + company.getState().toString());
                map.put("user-id", "" + (user != null ? user.getID() : -1));
                if (state.equals(State.CALLING))
                    map.put("elapsed-in-sec", "" + company.getStateCountdown().elapsed());

                arr.add(new JSONObject(map));
            });
        }

        var map = new HashMap<String, JSONArray>(Map.of("companies", arr));
        return new JSONObject(map);
    }

    private void backupJSON() {
        var json = new JSONObject();
        var usersArray = new JSONArray();
        var companiesArray= new JSONArray();

        usersManager.getAll().forEach(user -> {
            var mappedUser = new HashMap<>();
            
            mappedUser.put("id", "u" + user.getID());
            mappedUser.put("name", user.getName());
            mappedUser.put("secret", user.getSecret());
            mappedUser.put("status", user.isWhat().toString());
            
            usersArray.add(new JSONObject(mappedUser));
        });

        companiesManager.getAll().forEach(company -> {
            var mappedCompany = new JSONObject();

            mappedCompany.put("id", "c"+ company.getID());
            mappedCompany.put("name", company.getName());
            mappedCompany.put("table", "t" + company.getTableNumber());
            mappedCompany.put("state", company.getState().toString());

            var stateUser = company.getStateUser();
            mappedCompany.put("state-user", "u" + (stateUser != null ? stateUser.getID() : ""));

            var arrayRegistered = new JSONArray();
            company.getUnmodifiableQueue().forEach(uINc -> {
                arrayRegistered.add("u" + uINc.getID());
            });
            mappedCompany.put("registered", arrayRegistered);

            companiesArray.add(mappedCompany);
        });

        json.put("time", DateTimeFormatter.ofPattern("HH:mm:ss").format(LocalDateTime.now()));
        json.put("users", usersArray);
        json.put("companies", companiesArray);

        try {
            Path backupPath = Path.of("backup.json");
            Path oldBackupPath = Path.of("backup-old.json");

            if (Files.exists(backupPath)) {
                Files.move(backupPath, oldBackupPath, StandardCopyOption.REPLACE_EXISTING);
            }

            FileWriter fileWriter = new FileWriter(backupPath.toFile());
            fileWriter.write(json.toJSONString());
            fileWriter.close();
        } catch (IOException e) { }
    }

}
