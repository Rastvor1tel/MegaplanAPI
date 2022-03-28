<?php

class MegaplanAPI {
	private string $token;

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

		$link = curl_init('https://gaps2.megaplan.ru/api/v3/auth/access_token');

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

	/**
	 * Получение списка задач
	 * @param int $limit
	 * @return mixed
	 */
	public function getTasks($limit = 100) {
		$result = [];

		$params = json_encode([
			"limit" => $limit,
			/*"pageAfter" => [
				"contentType" => "Task",
				"id" => 1057029
			]*/
		]);

		$headers = [
			"AUTHORIZATION: Bearer {$this->token}"
		];

		$link = curl_init("https://gaps2.megaplan.ru/api/v3/task?{$params}");

		curl_setopt($link, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($link, CURLOPT_HTTPHEADER, $headers);

		$output = curl_exec($link);

		curl_close($link);
		print_r($output);
		$result = json_decode($output, true)["data"];

		return $result;
	}

	public function getTask($id) {
		$result = [];

		$headers = [
			"AUTHORIZATION: Bearer {$this->token}"
		];

		$link = curl_init("https://gaps2.megaplan.ru/api/v3/task/{$id}");

		curl_setopt($link, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($link, CURLOPT_HTTPHEADER, $headers);

		$output = curl_exec($link);

		curl_close($link);
		print_r($output);
		$result = json_decode($output, true)["data"];

		return $result;
	}

	/**
	 * Получение списка проектов
	 * @param int $limit
	 * @return mixed
	 */
	public function getProjects($limit = 100) {
		$result = [];

		$params = json_encode([
			"limit" => $limit,
		]);

		$headers = [
			"AUTHORIZATION: Bearer {$this->token}"
		];

		$link = curl_init("https://gaps2.megaplan.ru/api/v3/project?{$params}");

		curl_setopt($link, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($link, CURLOPT_HTTPHEADER, $headers);

		$output = curl_exec($link);

		curl_close($link);

		$result = json_decode($output, true)["data"];

		return $result;
	}

	public function getProject($id) {
		$result = [];

		$headers = [
			"AUTHORIZATION: Bearer {$this->token}"
		];

		$link = curl_init("https://gaps2.megaplan.ru/api/v3/project/{$id}");

		curl_setopt($link, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($link, CURLOPT_HTTPHEADER, $headers);

		$output = curl_exec($link);

		curl_close($link);

		$result = json_decode($output, true)["data"];

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
		$result["NAME"] = $this->getProject($id)["name"];
		$result["USERS"] = $this->getProjectUsers($id);

		return $result;
	}

	public function getProjectAuditors($id) {
		$result = [];

		$headers = [
			"AUTHORIZATION: Bearer {$this->token}"
		];

		$link = curl_init("https://gaps2.megaplan.ru/api/v3/project/{$id}/auditors");

		curl_setopt($link, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($link, CURLOPT_HTTPHEADER, $headers);

		$output = curl_exec($link);

		curl_close($link);

		$result = json_decode($output, true)["data"];

		return $result;
	}

	public function getProjectExecutors($id) {
		$result = [];

		$headers = [
			"AUTHORIZATION: Bearer {$this->token}"
		];

		$link = curl_init("https://gaps2.megaplan.ru/api/v3/project/{$id}/executors");

		curl_setopt($link, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($link, CURLOPT_HTTPHEADER, $headers);

		$output = curl_exec($link);

		curl_close($link);

		$result = json_decode($output, true)["data"];

		return $result;
	}
}