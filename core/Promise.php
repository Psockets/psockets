<?php

class PromiseState {
    const PENDING = 0;
    const FULFILLED = 1;
    const REJECTED = 2;
}

class Promise {
    private $successHandlers;
    private $failHandlers;
    private $finallyHandlers;
    private $state;
    private $resolveData;
    private $rejectReason;

    public function __construct() {
        $this->successHandlers = array();
        $this->failHandlers = array();
        $this->finallyHandlers = array();
        $this->state = PromiseState::PENDING;
        $this->resolveData = NULL;
        $this->rejectReason = NULL;
    }

    public function getState() {
        return $this->state;
    }

    public function then($success, $fail = NULL) {
        if ($this->state == PromiseState::FULFILLED) {
            call_user_func($success, $this->resolveData);
        } else {
            $this->successHandlers[] = $success;
        }

        if ($fail !== NULL) {
            return $this->catch($fail);
        }

        return $this;
    }

    public function catch($fail) {
        if ($this->state == PromiseState::REJECTED) {
            call_user_func($fail, $this->rejectReason);
        } else {
            $this->failHandlers[] = $fail;
        }

        return $this;
    }

    public function finally($finally) {
        if ($this->state != PromiseState::PENDING) {
            call_user_func($finally);
        } else {
            $this->finallyHandlers[] = $finally;
        }

        return $this;
    }

    public function resolve($data = NULL) {
        $this->state = PromiseState::FULFILLED;
        $this->resolveData = $data;

        try {
            foreach ($this->successHandlers as $success) {
                call_user_func($success, $data);
            }

            foreach ($this->finallyHandlers as $finally) {
                call_user_func($finally);
            }
        } catch (Exception $e) {
            $this->reject();
        }
    }

    public function reject(Exception $exception) {
        $this->state = PromiseState::REJECTED;
        $this->rejectReason = $exception;

        try {
            foreach ($this->failHandlers as $fail) {
                call_user_func($fail, $exception);
            }
        } catch (Exception $e) {}

        foreach ($this->finallyHandlers as $finally) {
            call_user_func($finally);
        }
    }
}
