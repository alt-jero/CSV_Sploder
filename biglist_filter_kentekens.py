#!/usr/bin/env python
from __future__ import print_function

import time
import csv

import sys  
import os
import time

start_time = time.time()

def main():  
	inputFile = os.path.realpath("./Open_Data_RDW__Gekentekende_voertuigen.csv")

	if not os.path.isfile(inputFile):
		print("File path={} does not exist.".format(inputFile))
		sys.exit()

	with open(inputFile) as fp:
		outputFiles = []
		for cnt, line in enumerate(fp):
			row = csv.reader([line]).next()
			if cnt == 0:
				index = ""
				for n, col in enumerate(row):
					if n == 0:
						index = col
					outputFiles.append(openOutputFile(n))
					outputFiles[n].write(str(index)+','+str(col)+"\n")
			else:
				index = ""
				for n, col in enumerate(row):
					if n == 0:
						index = col
					if col == '' or col == 'N.v.t.':
						continue
					outputFiles[n].write(str(index)+','+str(col)+"\n")
			# print("Line {}: {}".format(cnt, list(row)))
			if cnt % 100 == 0:
				printUpdate(fp)
			if cnt > 100000:
				break

		for file in outputFiles:
			file.close()
	print('')
def printUpdate(fp):
	print("\rPosition: {} Time: {} Remaining: {} Total Expected: {}".format( round(percentageOfFile(fp),3), formatTime(timeElapsed()), formatTime(timeRemaining(fp)), totalTime(fp)), end='')

def formatTime(t):
	return time.strftime('%H:%M:%S', time.gmtime(t))

def timeElapsed():
	return time.time() - start_time

def timeRemaining(fp):
	percent = percentageOfFile(fp)
	return timeElapsed() * (100 - percent) / percent

def totalTime(fp):
	return formatTime(timeElapsed() + timeRemaining(fp))

def percentageOfFile(fp):
	return float(fp.tell()) / float(os.fstat(fp.fileno()).st_size) * 100

def openOutputFile(number):
	return open(os.path.realpath('./Biglist_Outputs/'+str(number)+'.csv'), 'wb')

if __name__ == '__main__':  
	main()