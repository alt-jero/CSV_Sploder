#!/usr/bin/env perl

### Test program

use Text::CSV_XS;
use IO::File;

$input_file = "./Open_Data_RDW__Gekentekende_voertuigen.csv";
$input_size =  -s $input_file;

@output_files = ();
@paths = ();

if (! open ENTREE, "<", $input_file) {
	print "Could not open handle ENTREE for $input_file. $!\n";}


$start = time();


# my $csv_reader = Text::CSV_XS->new();
# while (my $row = $csv_reader->getline (<ENTREE>)) {
# 	print "$row->[2]\t$row->[0]\n";
# }
# $csv->eof or $csv->error_diag ();

sub openOutputFile {
	my $index = shift;
	my $file_name = "./Biglist_Outputs/$index.csv";
	my $fh = new IO::File "> $file_name" or die "Cannot open $file_name: $!";
	push @output_files, $fh;
}

sub writeOutputLine {
	my $index = shift;
	my $line = shift;
	my $outputFile = $output_files[$index];
	print {$outputFile} $line;
}

sub closeOutputFiles {
	foreach(@output_files) {
		close $_
	}
}

sub elapsed {
	return time() - $start;
}

sub percentage {
	return 100 * (tell ENTREE) / $input_size;
}

sub remaining {
	my $percentage = percentage();
	return (100 - $percentage) * elapsed() / $percentage;
}

sub totalTime {
	return elapsed() + remaining();
}

sub ncw_time_formatter
{
    my $seconds = shift;
    my $string = join ":", map { sprintf "%02d", $_} (gmtime($seconds))[7,2,1,0]; 
    $string=~s/\G00://g;
    return $string;
}

sub update_status_output
{
	$elapsed = ncw_time_formatter(elapsed());
	$percentage = sprintf('%.2f',percentage());
	$remaining = ncw_time_formatter(remaining());
	$total = ncw_time_formatter(totalTime());
	print "\e[K\r$percentage%\tElapsed:$elapsed\tRemaining:$remaining\tTotal Expected Time: $total";
}


my $csv_reader = Text::CSV_XS->new();
my @columns;
my $row = 0;
my $col = 0;

while (<ENTREE>) {
	$csv_reader->parse($_);
	@columns = $csv_reader->fields();
	$col = -1;
	if( $row == 0 ) {
		foreach(@columns) {
			$col++;
			openOutputFile($col);
			writeOutputLine($col, "$columns[0],$_\n");
		}
	} else {
		foreach(@columns) {
			$col++;
			if($_ eq "") {
				next;
			}
			writeOutputLine($col, "$columns[0],$columns[$col]\n");
		}
	}
	if( $row % 3) {
		update_status_output();
	}
	if( $row > 100000 ) {
		last;
	}
	$row++;
}

$duration = elapsed();

print "\nReading took $duration seconds.\n";
closeOutputFiles;
close ENTREE;