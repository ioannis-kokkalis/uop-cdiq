package gr.uop.model;

public class Company {

    public enum State {
        AVAILABLE("available"),
        CALLING("calling"),
        CALLING_TIMEOUT("calling-timeout"),
        OCCUPIED("occupied"),
        PAUSED("paused");

        private final String value;
        private User user;

        private State(String value) {
            this.value = value;
            this.user = null;
        }

        public void setUser(User user) {
            this.user = user;
        }

        /**
         * @return class {@link User} instance, or {@code null} when user is not relevant for the state
         */
        public User getUser() {
            if (this == AVAILABLE ||
                    this == PAUSED)
                return null;
            return this.user;
        }

        @Override
        public String toString() {
            return value;
        }
    }

    private final int ID;
    private final String name;
    private final int tableNumber;

    private State state;

    // private final Queue<User> waitingQ; // DOING
    // private final Queue<User> unavailableQ;

    public Company(int ID, String name, int tableNumber) {
        this.ID = ID;
        this.name = name;
        this.tableNumber = tableNumber;

        this.state = State.AVAILABLE;
    }

    // ---

    public int getID() {
        return ID;
    }

    public String getName() {
        return name;
    }

    public int getTableNumber() {
        return tableNumber;
    }

    public State getState() {
        return state;
    }

    public void setState(State state) {
        this.state = state;
    }

    @Override
    public String toString() {
        return this.ID + " \"" + this.name + "\" " + "(" + this.tableNumber + ") is |" + this.state + "|";
    }

}
