package gr.uop;

import java.util.Scanner;

import gr.uop.network.Network;
import gr.uop.network.NetworkException;

public class App {

    public static void main(String[] args) {
        Network network = null;
        // Model model = null;
        try {
            int port = Integer.parseInt(args[0]);

            // model = new Model(); // attempt to load from local backup?
            // System.out.println("Model started.");

            network = new Network(port);
            System.out.println("Network started on port \"" + port + "\".");

            CLI();
        } catch (NumberFormatException e) {
            System.err.println("Invlaid port input. Given \"" + args[0] + ". Check pom.xml");
        } catch (NetworkException e) {
            System.err.println(e.getMessage());
        } finally {
            if (network != null)
                network.shutdown();
            // if (model != null)
            //     model.shutdown();
        }
    }

    public static void CLI() {
        var scanner = new Scanner(System.in);

        while (scanner.hasNext()) {
            if (scanner.nextLine().equals("exit")) {
                System.out.println("Exiting...");
                break;
            } else
                System.out.println("Enter 'exit' to close the server properly.");
        }

        scanner.close();
    }

}
