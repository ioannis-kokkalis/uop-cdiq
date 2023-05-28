package gr.uop.model;

import java.util.Collection;
import java.util.LinkedList;
import java.util.List;

public class User {

    public enum Status {
        WAITING("waiting"),
        INTERVIEW("interview");

        private final String value;

        private Status(String value) {
            this.value = value;
        }

        @Override
        public String toString() {
            return value;
        }
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

    public Status getStatus() {
        return status;
    }

    public Collection<Company> getCompaniesRegisteredAt() {
        return this.companiesRegisteredAt;
    }

    public void setStatus(Status status) {
        this.status = status;
    }

    @Override
    public String toString() {
        return this.ID + " \"" + this.name + "\" " + "[" + this.secret + "]";
    }

}
