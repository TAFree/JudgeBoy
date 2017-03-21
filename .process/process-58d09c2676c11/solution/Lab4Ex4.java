import java.util.Scanner;

public class Lab4Ex4 {

	public static void main(String[] args) {
		System.out.print("Enter an integer, or quit with Q or q: ");
		Scanner input = new Scanner(System.in);
		String str = input.next();
		int max = 0;
		int countMax = 1;
		int sum = 0;
		int count = 0;

		while (!str.equalsIgnoreCase("Q")) {
			int check = Integer.parseInt(str);
			if (check > max) {
				max = check;
				countMax = 1;
			} else if (check == max) {
				countMax++;
			}
			sum += check;
			count++;
			System.out.print("Enter an integer, or quit with Q or q: ");
			str = input.next();

		}
		double avg = (double) sum / count;

		System.out.println("The largest number is " + max);
		System.out.println("The count for the largest number is " + countMax);
		System.out.printf("The average is %4.2f\n", avg);
		input.close();

		input.close();
	}

}
