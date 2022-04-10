<?php

class MegaplanAPI {
	private string $token;
	private string $url = "https://gaps2.megaplan.ru";

	public function __construct(string $login = "", string $password = "", string $token = "") {
		if ($token) {
			$this->token = $token;
		} else {
			$this->auth($login, $password);
		}
	}

	/**
	 * Авторизация
	 *
	 * @param string $login Логин
	 * @param string $password Пароль
	 * @return void
	 */
	public function auth($login, $password): void {
		$params = [
			"username"   => $login,
			"password"   => $password,
			"grant_type" => "password"
		];

		$link = curl_init("{$this->url}/api/v3/auth/access_token");

		curl_setopt($link, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($link, CURLOPT_POST, 1);
		curl_setopt($link, CURLOPT_POSTFIELDS, http_build_query($params, '', '&'));

		$output = curl_exec($link);

		curl_close($link);

		if ($output === false) {
			echo 'cURL Error: ' . curl_error($link);
			return;
		}
		$response = json_decode($output);

		if (!$response->error) {
			$result = $response->access_token;
			$this->token = $result;
			echo $result;
		}
	}

	private function requestExec($path) {
		$headers = [
			"AUTHORIZATION: Bearer {$this->token}"
		];
		$link = curl_init("{$this->url}{$path}");
		curl_setopt($link, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($link, CURLOPT_HTTPHEADER, $headers);
		$output = curl_exec($link);
		curl_close($link);
		$result = json_decode($output, true)["data"];
		return $result;
	}

	/**
	 * Получение списка задач
	 * @param int $limit
	 * @return mixed
	 */
	public function getTasks($limit = 100, $item = false, $filter = false) {
		$params = [
			"limit" => $limit,
		];

		if ($item) $params["pageAfter"] = ["contentType" => "Task", "id" => $item];
		if ($filter) $params["filter"] = ["id" => $filter];

		$params = json_encode($params);

		$result = $this->requestExec("/api/v3/task?{$params}");

		return $result;
	}

	public function getTask($id) {
		$result = $this->requestExec("/api/v3/task/{$id}");
		return $result;
	}

	public function getTaskInfo($id) {
		$task = $this->getTask($id);
		$deadline = $task["deadline"]["day"] ? str_pad($task["deadline"]["day"], 2, '0', STR_PAD_LEFT) . "." . str_pad($task["deadline"]["month"] + 1, 2, '0', STR_PAD_LEFT) . "." . $task["deadline"]["year"] : "";
		$result = [
			"ID"       => $task["id"],
			"NAME"     => "{$task["name"]} (Мегаплан)",
			"TEXT"     => $task["statement"],
			"STATUS"   => $task["status"],
			"DEADLINE" => $deadline,
			"USERS"    => [
				"OWNER"       => $task["owner"]["name"],
				"RESPONSIBLE" => $task["responsible"]["name"] ?? $task["owner"]["name"],
			]
		];

		switch ($task["status"]) {
			case "complated":
			case "done":
				$result["STATUS"] = 5;
				break;
			case "accepted":
				$result["STATUS"] = 3;
				break;
			default:
				$result["STATUS"] = -2;
				break;
		}
		
		$taskUsers = [
			$task["owner"]["id"]       => $task["owner"]["name"],
			$task["responsible"]["id"] => $task["responsible"]["name"] ?? $task["owner"]["name"],
		];

		if ($task["executors"]) {
			foreach ($task["executors"] as $executor) {
				$result["USERS"]["EXECUTORS"][$executor["id"]] = $executor["name"];
				$taskUsers[$executor["id"]] = $executor["name"];
			}
		}

		if ($task["auditors"]) {
			foreach ($task["auditors"] as $auditor) {
				$result["USERS"]["AUDITORS"][$auditor["id"]] = $auditor["name"];
				$taskUsers[$auditor["id"]] = $auditor["name"];
			}
		}

		if ($task["parents"]) {
			$result["PROJECT"] = "{$task["parents"][0]["name"]} (Мегаплан)";
		}
		if ($task["comments"]) {
			$comments = array_reverse($this->getComments($id));
			foreach ($comments as $comment) {
				$result["COMMENTS"][] = [
					"TEXT"  => $comment["content"],
					"OWNER" => $taskUsers[$comment["owner"]["id"]],
					"DATE"  => $comment["timeCreated"]["value"],
				];
			}
		}
		return $result;
	}

	public function getComments($id) {
		$result = $this->requestExec("/api/v3/task/{$id}/comments");
		return $result;
	}

	/**
	 * Получение списка проектов
	 * @param int $limit
	 * @return mixed
	 */
	public function getProjects($limit = 100) {
		$params = json_encode([
			"limit" => $limit,
		]);

		$result = $this->requestExec("/api/v3/project?{$params}");
		return $result;
	}

	public function getProject($id) {
		$result = $this->requestExec("/api/v3/project/{$id}");
		return $result;
	}

	public function getProjectUsers($id) {
		$result = [];
		$result["OWNER"] = $this->getProject($id)["owner"]["name"];
		foreach ($this->getProjectAuditors($id) as $auditor)
			$result["USERS"][$auditor["id"]] = $auditor["name"];
		foreach ($this->getProjectExecutors($id) as $executor)
			$result["USERS"][$executor["id"]] = $executor["name"];

		return $result;
	}

	public function getProjectInfo($id) {
		$result = [];
		$result["ID"] = $this->getProject($id)["id"];
		$result["NAME"] = "{$this->getProject($id)["name"]} (Мегаплан)";
		$result["USERS"] = $this->getProjectUsers($id);

		return $result;
	}

	public function getProjectAuditors($id) {
		$result = $this->requestExec("/api/v3/project/{$id}/auditors");
		return $result;
	}

	public function getProjectExecutors($id) {
		$result = $this->requestExec("/api/v3/project/{$id}/executors");
		return $result;
	}
}