<?php
namespace backend\utils;

class DateConverter {
    public static function convertToSQL(string|int $timestamp, $format = "Y-m-d H:i:s") {
        if (is_string($timestamp)) {
            $timestamp = strtotime($timestamp);
            $timestamp = date($format, $timestamp);
        }
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