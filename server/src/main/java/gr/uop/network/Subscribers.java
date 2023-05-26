package gr.uop.network;

import java.util.HashMap;
import java.util.LinkedList;
import java.util.List;
import java.util.Map;

public class Subscribers {

    public enum Subscription {
        SECRETARY, MANAGER, PUBLIC_MONITOR
    }

    private Map<Subscription, LinkedList<Client>> subscriptionLists;

    public Subscribers() {
        subscriptionLists = new HashMap<>();
        for (Subscription subscription : Subscription.values()) {
            subscriptionLists.put(subscription, new LinkedList<>());
        }
    }

    public List<Client> getAll() {
        List<Client> merged = new LinkedList<>();
        for (Subscription values : Subscription.values()) {
            merged.addAll(getAll(values));
        }
        return merged;
    }

    public List<Client> getAll(Subscription subscription) {
        return new LinkedList<>(subscriptionLists.get(subscription));
    }

    public void add(Subscription subscription, Client client) {
        subscriptionLists.get(subscription).add(client);
    }

    public void remove(Client client) {
        subscriptionLists.values().forEach(list -> list.remove(client));
    }

}
