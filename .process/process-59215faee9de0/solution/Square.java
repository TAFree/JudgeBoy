public class Square extends GeometricObject {
	private double length = 1.0;

	/** Constructor */
	public Square() {
		super();
	}

	/** Constructor */
	public Square(double length, String color, Boolean filled) {
		super(color, filled);
		this.length = length;
	}

	@Override
	public double getArea() {
		return length * length;
	}

	@Override
	public double getPerimeter() {
		return 4.0 * length;
	}

	@Override
	public void display() {
		System.out.println("The Square object is " + "created on "
				+ super.getDateCreated() + "\ncolor: " + super.getColor()
				+ " and filled: " + super.isFilled() + "\nlength: " + length
				+ " area: " + getArea() + " and perimeter: " + getPerimeter());
	}
}
