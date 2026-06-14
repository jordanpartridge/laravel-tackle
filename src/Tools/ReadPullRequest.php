<?php

namespace Tackle\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Tools\Request;
use Tackle\Support\GitHubClient;
use Throwable;

class ReadPullRequest extends AbstractTool
{
    public function __construct(private GitHubClient $client) {}

    public function description(): string
    {
        return 'Fetch a GitHub pull request by number — returns the title, body, branch name (head ref), base branch, state, author, and comments. Use this (not ReadGitHubIssue) when the user references a PR number, especially when you need the branch name to pass to CommitAndPush.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'pr_number' => $schema->integer()
                ->description('GitHub pull request number.')
                ->required(),
        ];
    }

    public function handle(Request $request): string
    {
        if (! $this->client->configured()) {
            return 'GitHub is not configured. Set GITHUB_TOKEN (or run: gh auth login) and GITHUB_REPO in .env.';
        }

        $prNumber = $request->integer('pr_number', 0);

        if ($prNumber <= 0) {
            return 'pr_number is required.';
        }

        $repo = $this->client->repo();

        try {
            $prResponse = $this->client->get("repos/{$repo}/pulls/{$prNumber}");

            if (! $prResponse->successful()) {
                return "Could not fetch PR #{$prNumber}. Check that GITHUB_TOKEN and GITHUB_REPO are set and the PR number is correct.";
            }

            $commentsResponse = $this->client->get("repos/{$repo}/issues/{$prNumber}/comments", ['per_page' => 25]);
            $comments = $commentsResponse->successful() ? $commentsResponse->json() : [];

            return $this->format($prResponse->json(), $comments);
        } catch (Throwable $e) {
            return 'Error fetching pull request: ' . $e->getMessage();
        }
    }

    private function format(array $pr, array $comments): string
    {
        $number = $pr['number']            ?? '?';
        $title  = $pr['title']             ?? '?';
        $state  = $pr['state']             ?? '?';
        $author = $pr['user']['login']     ?? '?';
        $branch = $pr['head']['ref']       ?? '?';
        $base   = $pr['base']['ref']       ?? '?';
        $url    = $pr['html_url']          ?? '';
        $body   = trim($pr['body']         ?? '');

        $output  = "GitHub PR #{$number} — {$title}";
        $output .= "\nState: {$state} | Author: {$author}";
        $output .= "\nBranch: {$branch} → {$base}";

        if ($url) {
            $output .= "\nURL: {$url}";
        }

        if ($body) {
            $output .= "\n\n{$body}";
        }

        if (! empty($comments)) {
            $output .= "\n\n--- Comments ---";
            foreach ($comments as $comment) {
                $login   = $comment['user']['login'] ?? '?';
                $created = $comment['created_at']    ?? '';
                $text    = trim($comment['body']     ?? '');
                $output .= "\n\n[{$created}] {$login}:\n{$text}";
            }
        }

        return trim($output);
    }
}
