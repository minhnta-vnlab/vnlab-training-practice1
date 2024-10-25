<?php
namespace backend\utils;

class DateConverter {
    public static function convertToSQL(string|int $timestamp, $format = "Y-m-d H:i:s") {
        // If the timestamp is a string, handle it accordingly
        if (is_string($timestamp)) {
            // Remove fractional seconds if present
            $timestamp = preg_replace('/\.\d+/', '', $timestamp);
            // Convert to timestamp
            $timestamp = strtotime($timestamp);
        }

        // If the timestamp is an integer, format it
        if (is_int($timestamp)) {
            $timestamp = date($format, $timestamp);
        }

        return $timestamp;
    }

    public static function convertToTimestamp(string $timestamp, $format = "Y-m-d H:i:s") {
        if (is_string($timestamp)) {
            $timestamp = strtotime($timestamp);
        }

        return $timestamp;
    }
}