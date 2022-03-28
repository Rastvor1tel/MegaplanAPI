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
	private function auth($login, $password): void {
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
	 *
	 */
	public function getTasks() {
		$result = [];

		$params = json_encode([
			"limit" => 5,
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

		$result = json_decode($output, true)["data"];

		return $result;
	}

}