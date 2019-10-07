<?php


namespace WEEEOpen\WEEEHire;


use Exception;

class Ldap {
	protected $ds;
	protected $usersDn;
	protected $invitesDn;
	protected $url;
	protected $starttls;
	private $apcu = false;
	public static $multivalued = ['memberof' => true, 'sshpublickey' => true, 'weeelabnickname' => true];

	public function __construct(string $url, string $bindDn, string $password, string $usersDn, string $invitesDn, bool $startTls = true) {
		$this->url = $url;
		$this->starttls = $startTls;
		$this->usersDn = $usersDn;
		$this->invitesDn = $invitesDn;
		$this->ds = ldap_connect($url);
		if(!$this->ds) {
			throw new LdapException('Cannot connect to LDAP server');
		}
		if($startTls) {
			if(!ldap_start_tls($this->ds)) {
				throw new LdapException('Cannot STARTTLS with LDAP server');
			}
		}
		if(!ldap_bind($this->ds, $bindDn, $password)) {
			throw new LdapException('Bind with LDAP server failed');
		}
		if(extension_loaded('apcu') && ini_get('apcu.enabled')) {
			$this->apcu = true;
		} else {
			error_log('APCu is not enabled, please enable it, I beg you!');
		}
	}

	public function getRecruiters(): array {
		if($this->apcu) {
			$cached = false;
			/** @noinspection PhpComposerExtensionStubsInspection */
			$recruiters = apcu_fetch('recruiters', $cached);
			/** @noinspection PhpComposerExtensionStubsInspection */
			if($cached) {
				return $recruiters;
			}
		}

		$sr = ldap_search($this->ds, $this->usersDn, WEEEHIRE_LDAP_SHOW_USERS_FILTER, ['cn', 'telegramnickname']);
		if(!$sr) {
			throw new LdapException('Cannot search recruiters');
		}
		$count = ldap_count_entries($this->ds, $sr);
		if($count === 0) {
			return [];
		} else {
			$recruiters = [];
			$entries = ldap_get_entries($this->ds, $sr);
			unset($entries['count']);
			foreach($entries as $entry) {
				if(isset($entry['cn'])) {
					$cn = $entry['cn'][0];
				} else {
					$cn = "⚠️ Missing cn";
				}
				if(isset($entry['telegramnickname'])) {
					$tgn = $entry['telegramnickname'][0];
				} else {
					$tgn = "⚠️ Missing telegram nickname";
				}
				$recruiters[$cn] = [$cn, $tgn];
			}
			ksort($recruiters, SORT_STRING | SORT_FLAG_CASE);
			if($this->apcu) {
				/** @noinspection PhpComposerExtensionStubsInspection */
				apcu_store('recruiters', $recruiters, 3600); // 1 hour
			}
			return $recruiters;
		}
	}

	/**
	 * @param User $user User to invite
	 *
	 * @return string The invite URL
	 * @throws Exception
	 */
	public function createInvite(User $user): string {
		$inviteCode = strtoupper(bin2hex(random_bytes(12)));
		$result = ldap_add($this->ds, "inviteCode=$inviteCode," . $this->invitesDn, [
			'cn' => $user->name . ' ' . $user->surname, // Mandatory attribute
			'objectclass' => [
				'inviteCodeContainer',
				'schacLinkageIdentifiers',
				'schacPersonalCharacteristics',
				'telegramAccount',
				'weeeOpenPerson',
			],
			'givenname' => $user->name,
			'sn' => $user->surname,
			'mail' => Utils::politoMail($user->matricola),
			'schacpersonaluniquecode' => $user->matricola,
			'degreecourse' => $user->degreecourse
		]);
		if(!$result) {
			throw new LdapException('Cannot create invite');
		}
		return WEEEHIRE_INVITE_LINK . $inviteCode;
	}

	protected static function simplify(array $result): array {
		// Same function in Crauto, too
		$things = [];
		foreach($result as $k => $v) {
			// dn seems to be always null!?
			if(!is_int($k) && $k !== 'count' && $k !== 'dn') {
				$attr = strtolower($k); // Should be already done, but repeat it anyway
				$things[$attr] = $v[0];
			}
		}
		return $things;
	}

}