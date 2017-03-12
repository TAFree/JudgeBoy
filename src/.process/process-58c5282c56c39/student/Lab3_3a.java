import java.lang.Math;
import java.util.Scanner;

public class Lab3_3a {

    public static void main(String []args){
        Scanner input = new Scanner(System.in);
        System.out.print("scissor (0), rock (1), paper (2): ");
        int user = input.nextInt();
        String _user = "";
        switch (user) {
            case 0:
                _user = "scissor";
                break;
            case 1:
                _user = "rock";
                break;
            case 2:
                _user = "paper";
                break;
            default:
                break;
        }
        int cp = (int)(Math.random() * 3);
        String _cp = "";
        switch (cp) {
            case 0:
                _cp = "scissor";
                break;
            case 1:
                _cp = "rock";
                break;
            case 2:
                _cp = "paper";
                break;
            default:
                break;
        }
        if ((cp == 2 && user == 0) || (cp == 0 && user == 2)) {
            int tmp;
            tmp = cp;
            cp = user;
            user = tmp;
        }
        String _res = "";
        if (cp > user) {
            _res = ". You lose!";
        }
        else if (cp == user) {
            _res = " too. It is a draw!";
        }
        else {
            _res = ". You win!";
        }
        System.out.print("The computer is " + _cp + ". You are " + _user + _res);
     }
     
}
