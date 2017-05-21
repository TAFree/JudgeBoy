public class Lab11Prob2 {
    public static void main(String[] args) {
        GeometricObject[] coll = new GeometricObject[3];
        for (int i = 0; i < coll.length; i++){
            coll[i] = newShape();
            coll[i].display();
        }
    }

    public static GeometricObject newShape() {
        switch ((int) (Math.random() * 2.0)) {
        case 0:
            return new Circle(Math.random(),"red", true);
        case 1:
            return new Square(Math.random(),"red", true);
        }
        return null;
    }
}

