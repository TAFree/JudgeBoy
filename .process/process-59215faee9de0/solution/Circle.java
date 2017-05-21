public class Circle extends GeometricObject {
	private double radius = 0.5;

	/** Constructor */
	public Circle() {
		super();
	}

	/** Constructor */
	public Circle(double radius, String color, Boolean filled) {
		super(color, filled);
		this.radius = radius;
	}

	@Override
	public double getArea() {
		return Math.PI * radius * radius;
	}

	@Override
	public double getPerimeter() {
		return 2.0 * Math.PI * radius;
	}

	@Override
	public void display() {
		System.out.println("The Circle object is " + "created on "
				+ super.getDateCreated() + "\ncolor: " + super.getColor()
				+ " and filled: " + super.isFilled() + "\nradius: " + radius
				+ "area: " + getArea() + " and perimeter: " + getPerimeter());
	}
}