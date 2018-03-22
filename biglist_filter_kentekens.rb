#!/usr/bin/env ruby

require 'csv'

$StartTime = Time.now

$files = []
def openOutputFiles(row)
	File.open('./Biglist_Outputs/index.csv', 'w') do |indexFile|
		row.each_with_index do |field, index|
			indexFile.puts(row[0] + ',' + field)
			$files << File.new('./Biglist_Outputs/' + index.to_s + '.csv', 'w')
			$files[index].puts(row[0] + ',' + field)
		end
	end
end

def writeOutput(row)
	row.each_with_index do |field, index|
		if field === nil
			next
		end
		if field === ''
			next
		end
		$files[index].puts(row[0] + ',' + field)
	end
end

def percentageOfFile(linenum)
	(linenum * 100 / 13500000.0)
end

def elapsed()
	Time.now - $StartTime
end

def remaining(linenum)
	elapsed * (100 - percentageOfFile(linenum)) / percentageOfFile(linenum)
end

def formatTime(total_time)
	[total_time / 3600, total_time / 60 % 60, total_time % 60].map { |t| t.to_i.to_s.rjust(2,'0') }.join(':')
end

def totalTime(linenum)
	formatTime(elapsed() + remaining(linenum))
end


rownum = 0

CSV.foreach('Open_Data_RDW__Gekentekende_voertuigen.csv') do |row|
	if rownum == 0
		openOutputFiles row
	else
		writeOutput row #row.inspect
	end
	rownum += 1
	if rownum % 10 == 0
		print "\r"
		print sprintf('%.3f', percentageOfFile(rownum).round(3))
		print '% Elapsed:'
		print formatTime elapsed
		print ' Remaining:'
		print formatTime remaining rownum
		print ' Total Expected:'
		print totalTime rownum

		# puts
		# break
	end
	if rownum > 100000
		break
	end
end

$files.each do |file|
	file.close
end

print "\n"