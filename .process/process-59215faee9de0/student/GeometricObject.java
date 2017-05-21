public class GeometricObject {
	private String color = "white";
	private boolean filled;
	private java.util.Date dateCreated;

	/** Construct a default geometric object */
	public GeometricObject() {
		dateCreated = new java.util.Date();
	}

	/**
	 * Construct a geometric object with the specified color and filled value
	 */
	public GeometricObject(String color, boolean filled) {
		dateCreated = new java.util.Date();
		this.color = color;
		this.filled = filled;
	}

	/** Return color */
	public String getColor() {
		return color;
	}

	/**
	 * Return filled. Since filled is boolean, its get method is named isFilled
	 */
	public boolean isFilled() {
		return filled;
	}

	/** Get dateCreated */
	public java.util.Date getDateCreated() {
		return dateCreated;
	}

	/** Return area */
	public double getArea() {
		return 0.0;
	}

	/** Return perimeter */
	public double getPerimeter() {
		return 0.0;
	}

	public void display() {
		System.out.println("The Geometric object is " + "created on "
				+ dateCreated + "\ncolor: " + color + " and filled: " + filled);
	}
}

