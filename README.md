# Challenge 044: Look and Say Sequence

Application used to process N look-and-say sequences in parallel

Sequences are sent to parallel processing socket servers for processing as the previous sequence iteration completes. So
as sequence results are returned from one server for the sequence they are piped to the next processing server connection
to begin parsing even when the previous iteration is still in flight.

At the expense of reduced performance this offers a more stable rate of memory consumption since we do not need to process
each sequence string in the same thread.

## Benchmarks

Seed: 132

Iterations:
 * 10: 6 seconds
 * 20: 
 * 30: 
 * 40: 
 * 50: 
 * 60: 450.0 seconds
 * 70: 
 * 80: __refuse to wait that long__
 
![System Performance](performance.png)

## Install

* `composer install`

## Usage

    Usage:
      php index.php <seed> <iterations>
    
    Arguments:
      seed        Initial seed sequence
      iterations  Number iterations to process
     
## Tests

* __no tests__
