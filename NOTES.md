NOTES
=====

All graders are assumed passive by now.
The web module actively dispatches tasks to graders.
Another possible scheduling policy is to let graders query the web module to find tasks to run.
But this polling strategy is resource consuming.

Currently API is planned but whether to develop remote graders or not depends on the schedule.

in data/assignments.json, the size limit is in Bytes
and the extension name limit is separated by "," or ", ". Values are like "c, tar.gz", etc.
all submiitted files are *required* to have an extension name


ajax quota update when submitting

ONE MUST ASSUME STUDENT CODE MALICIOUS.
