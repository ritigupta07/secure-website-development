import numpy as np
import csv
import sys
def sigmoid(z):
    return 1/(1+np.exp(-z))

def predict(theta1, theta2, X, y):
    m = len(y)
    ones = np.ones((m,1))
    a1 = np.hstack((ones, X))
    a2 = sigmoid(np.asarray(a1,dtype=np.float32) @ np.asarray(theta1,dtype=np.float32).T)
    a2 = np.hstack((ones, a2))
    h = sigmoid(a2 @ np.asarray(theta2,dtype=np.float32).T)
    return np.argmax(h, axis = 1) + 1

data = np.loadtxt(sys.argv[1],delimiter=",")
fac = 0.99 / 255
X = np.asfarray(data[1:, 1:]) * fac + 0.01

y = np.asfarray(data[1:, :1])

# read the theta weights file
with open(sys.argv[2], 'r') as csvfile:
    reader = csv.reader(csvfile)
    theta1_opt = [[e for e in r] for r in reader]

# read the theta weights file
with open(sys.argv[3], 'r') as csvfile:
    reader = csv.reader(csvfile)
    theta2_opt = [[e for e in r] for r in reader]

pred = predict(theta1_opt, theta2_opt, X, y)
print(np.mean(pred == y.flatten()) * 100)
