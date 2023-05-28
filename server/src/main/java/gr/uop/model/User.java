package gr.uop.model;

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

    public User(String name, String secret) {
        this.ID = generateID();
        this.name = name;
        this.secret = secret;

        this.status = Status.WAITING;
    }

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

    public void setStatus(Status status) {
        this.status = status;
    }

    @Override
    public String toString() {
        return this.ID + " \"" + this.name + "\" " + "[" + this.secret + "]";
    }

}
