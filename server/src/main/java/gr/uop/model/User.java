package gr.uop.model;

import java.util.Collection;
import java.util.LinkedList;
import java.util.List;

public class User {

    public enum Status {
        WAITING,
        CALLING,
        INTERVIEW;
    }

    private static int NEXT_ID = 1;

    private final int ID;
    private final String name;
    private final String secret;

    private Status status;
    private List<Company> companiesRegisteredAt;

    public User(String name, String secret) {
        this.ID = generateID();
        this.name = name;
        this.secret = secret;

        this.status = Status.WAITING;
        this.companiesRegisteredAt = new LinkedList<>();
    }

    public Status isWhat() {
        return status;
    }
    
    public void isNow(Status status) {
        this.status = status;
    }

    public boolean is(Status status) {
        return this.status.equals(status);
    }

    // ---

    private int generateID() {
        return NEXT_ID++;
    }

    // ---

    public int getID() {
        return ID;
    }

    public String getName() {
        return name;
    }

    public String getSecret() {
        return secret;
    }

    public Collection<Company> getCompaniesRegisteredAt() {
        return this.companiesRegisteredAt;
    }

    @Override
    public String toString() {
        return this.ID + " \"" + this.name + "\" " + "[" + this.secret + "] is " + status;
    }

}
