package gr.uop.model;

import java.util.concurrent.*;

public class Countdown {

    private ScheduledExecutorService scheduledExecutorService;
    private Future<?> future;

    private final int totalSeconds;

    public Countdown(int seconds, Runnable onCompletion) {
        totalSeconds = seconds;
        scheduledExecutorService = Executors.newSingleThreadScheduledExecutor();

        future = scheduledExecutorService.schedule(() -> {
            scheduledExecutorService.shutdown();
            onCompletion.run();
        }, totalSeconds, TimeUnit.SECONDS);
    }

    /**
     * @return seconds elapsed since creation
     */
    public int elapsed() {
        if (future.isDone()) {
            return totalSeconds;
        } else {
            long elapsed = totalSeconds - ((Delayed) future).getDelay(TimeUnit.SECONDS);
            return (int) elapsed;
        }
    }
}
