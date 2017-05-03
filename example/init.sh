#!/usr/bin/env bash

cd episodes;

for i in $(seq 70)
do
	touch "test 00$i.avi"
done
