package gr.uop;

import java.util.Scanner;

import gr.uop.model.Model;
import gr.uop.model.ModelException;
import gr.uop.network.Network;
import gr.uop.network.NetworkException;

public class App {

    public final static TaskProcessor TASK_PROCESSOR = new TaskProcessor();

    public static Network network;

    public static void main(String[] args) {        
        Model model = null;
        try {
            int port = Integer.parseInt(args[0]);

            model = Model.initiate();
            App.consoleLog("Model started.");

            network = new Network(port, model);
            App.consoleLog("Network started on port \"" + port + "\".");

            CLI();
        } catch (NumberFormatException e) {
            App.consoleLogError("Invlaid server listening port.", "Given \"" + args[0] + ", check pom.xml.");
        } catch (NetworkException | ModelException e) {
            App.consoleLogError(e.toString());
        } finally {
            if (network != null)
                network.shutdown();
            if (model != null)
                model.shutdown();
            App.TASK_PROCESSOR.shutdown();
        }
    }

    public static void CLI() {
        var scanner = new Scanner(System.in);

        while (scanner.hasNext()) {
            if (scanner.nextLine().equals("exit")) {
                App.consoleLog("Exiting...");
                break;
            } else
                App.consoleLog("Enter 'exit' to close the server properly.");
        }

        scanner.close();
    }

    public static void consoleLog(String main, String... extra) {
        var sb = new StringBuilder();

        sb.append("\n===========\n").append(main);

        for (String e : extra)
            sb.append("\n-----------\n").append(e);

        sb.append("\n===========\n");

        System.out.println(sb.toString());
    }

    public static void consoleLogError(String main, String... extra) {
        var sb = new StringBuilder();

        sb.append("\u001B[31m\n===========\n").append(main);

        for (String e : extra)
            sb.append("\n-----------\n").append(e);

        sb.append("\n===========\n\u001B[0m");

        System.err.println(sb.toString());
    }

}
