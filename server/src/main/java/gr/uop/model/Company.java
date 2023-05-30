package gr.uop.model;

import java.util.Collection;
import java.util.Collections;
import java.util.LinkedList;
import java.util.Queue;

import gr.uop.App;
import gr.uop.model.Model.Action;
import gr.uop.model.User.Status;

public class Company {

    private final static int MAX_CALLING_SECONDS = 10; // TODO change to 3 minutes (3*60)

    public enum State {
        AVAILABLE("available"),
        CALLING("calling"),
        CALLING_TIMEOUT("calling-timeout"),
        OCCUPIED("occupied"),
        PAUSED("paused");

        public final String value;

        private State(String value) {
            this.value = value;
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
    private User stateUser;
    private Countdown stateCountdown;

    private final Queue<User> companyQueue;
    private int update; // BUG overflow if it increases too many yimes

    public Company(int ID, String name, int tableNumber) {
        this.ID = ID;
        this.name = name;
        this.tableNumber = tableNumber;

        this.state = State.PAUSED;
        this.companyQueue = new LinkedList<>();
        this.update = 0;
    }

    /**
     * @return {@code true} when changes occured, else {@code false}
     */
    public void update(Model model) {
        update++;
        switch(this.state) {
            case AVAILABLE:
                User waitingUser = null;
                
                for (User user : this.companyQueue) {
                    if (user.is(Status.WAITING)) {
                        waitingUser = user;
                        break;
                    }
                }
                
                if(waitingUser == null)
                    break;

                var validUpdateToTriggerCallingTimeout = update;
                var countdown = new Countdown(MAX_CALLING_SECONDS, () -> {
                    App.TASK_PROCESSOR.process(() -> {
                        if (validUpdateToTriggerCallingTimeout == update) {
                            // no other update happened in this company
                            model.handleAction(Action.CALLING_TIMEDOUT, null, this.getID());
                        }
                        // else it has changed from CALLING before the countdown ends, skip timeout
                    });
                });
                
                waitingUser.isNow(Status.CALLING);
                
                this.state = State.CALLING;
                this.setStateUser(waitingUser);
                this.setStateCountdown(countdown);
                
                break;

            case CALLING:
            case CALLING_TIMEOUT:
            case OCCUPIED:
            case PAUSED:
                // change occured before update call
                break;
        }
    }

    public void remove(User user) {
        companyQueue.remove(user);
    }

    public void add(User user) {
        if( user.getCompaniesRegisteredAt().contains(this) )
            return;

        this.companyQueue.add(user);
        user.getCompaniesRegisteredAt().add(this);
    }

    public Collection<User> getUnmodifiableQueue() {
        return Collections.unmodifiableCollection(companyQueue);
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

    public User getStateUser() {
        if (this.state == State.AVAILABLE ||
                this.state == State.PAUSED)
            return null;
        return this.stateUser;
    }

    public Countdown getStateCountdown() {
        if (this.state == State.CALLING)
            return this.stateCountdown;
        return null;
    }

    public void setState(State state) {
        this.state = state;
    }

    public void setStateUser(User user) {
        this.stateUser = user;
    }

    public void setStateCountdown(Countdown countdown) {
        this.stateCountdown = countdown;
    }

    @Override
    public String toString() {
        return this.ID + " \"" + this.name + "\" " + "(" + this.tableNumber + ") is |" + this.state + "|";
    }

}
